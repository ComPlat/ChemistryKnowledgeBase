#!/usr/bin/env python3
"""Standalone, DB-free prompt+extraction optimizer (runs without MediaWiki/PHP).

Loads the gold set + PDFs from eval/<topic>/, extracts each publication with the current prompt
via the OpenAI Responses API (structured JSON output), scores the extraction against the gold
field-by-field (numeric + unit aware; molecule columns are extracted but not scored without the
wiki DB), then asks the model to improve the prompt and repeats. Per iteration it appends
eval/<topic>/results/metrics.csv and regenerates the matplotlib trend (plot_eval_metrics.py).

Key is read from ~/.config/chemwiki/openai.key (or $OPENAI_API_KEY) — never from the repo.

Usage:
  python3 optimize_local.py --topic Photocatalytic_CO2_conversion \
      --prompt-file ../../../wikischema/MediaWiki/Prompt_import_Photocatalytic_CO2_conversion.wiki \
      --iterations 8 --limit 3 --model gpt-4o --export-prompt
"""
import argparse, base64, glob, json, os, re, subprocess, sys, time, urllib.request, urllib.error

HERE = os.path.dirname(os.path.abspath(__file__))
EXT = os.path.dirname(HERE)                       # .../ChemExtension
EVAL = os.path.join(EXT, "eval")
REPO = os.path.dirname(os.path.dirname(os.path.dirname(EXT)))  # repo root
API = "https://api.openai.com/v1/responses"

# ---------- unit handling (mirrors src/Eval/UnitConverter.php) ----------
FAMILIES = {
    "concentration": {"m": 1.0, "mm": 1e-3, "um": 1e-6, "nm": 1e-9, "pm": 1e-12, "mol/l": 1.0, "mmol/l": 1e-3},
    "potential": {"v": 1.0, "mv": 1e-3},
    "time": {"h": 1.0, "hr": 1.0, "min": 1 / 60, "s": 1 / 3600, "sec": 1 / 3600, "d": 24.0},
    "wavelength": {"nm": 1.0, "um": 1000.0, "a": 0.1},
    "percent": {"%": 1.0, "percent": 1.0},
}
EXPECTED_UNIT = {  # field -> (unit, family)
    "cat conc": ("um", "concentration"), "PS conc": ("mm", "concentration"),
    "e-D conc": ("m", "concentration"), "H-D conc": ("m", "concentration"),
    "host conc": ("m", "concentration"), "guest conc": ("m", "concentration"),
    "λexc": ("nm", "wavelength"), "irr time": ("h", "time"),
    "Quantum_yield__CO": ("%", "percent"),
}

def norm_unit(u):
    u = u.strip().lower().replace("µ", "u").replace("μ", "u")
    for ch in ("°", "^", " ", "·", "*"):
        u = u.replace(ch, "")
    return u

def parse_num(v):
    v = str(v).replace("×10^", "e").replace("x10^", "e")
    m = re.search(r"-?\d+(?:[.,]\d+)?(?:[eE][-+]?\d+)?", v)
    return float(m.group(0).replace(",", ".")) if m else None

def parse_value(v):
    v = str(v).strip()
    m = re.match(r"^[+-]?\d+(?:[.,]\d+)?(?:[eE][-+]?\d+)?", v)
    if m:
        return float(m.group(0).replace(",", ".")), norm_unit(v[m.end():])
    return None, norm_unit(v)

def convert(value, frm, to, fam):
    mp = FAMILIES.get(fam, {})
    frm = frm or to
    if frm not in mp or to not in mp:
        return None
    return value * mp[frm] / mp[to]

def is_empty(v):
    s = str(v).strip().lower()
    return s in ("", "n/a", "-")

def is_molecule(v):
    return str(v).startswith("Molecule:")

def values_match(field, gold, ext, tol=0.1):
    if field in EXPECTED_UNIT:
        unit, fam = EXPECTED_UNIT[field]
        gn, gu = parse_value(gold); en, eu = parse_value(ext)
        if gn is not None and en is not None:
            gc, ec = convert(gn, gu, unit, fam), convert(en, eu, unit, fam)
            if gc is not None and ec is not None:
                scale = max(abs(gc), abs(ec), 1e-12)
                return abs(gc - ec) <= tol * scale
    gn, en = parse_num(gold), parse_num(ext)
    if gn is not None and en is not None:
        scale = max(abs(gn), abs(en), 1e-12)
        return abs(gn - en) <= tol * scale
    return re.sub(r"\s+", " ", str(gold).strip().lower()) == re.sub(r"\s+", " ", str(ext).strip().lower())

def score_pub(gold_rows, ext_rows, tol=0.1):
    used, tp, gold_cells, ext_cells = set(), 0, 0, 0
    per_field, examples = {}, []
    def scorable(row):  # exclude molecule + empty cells
        return {k: v for k, v in row.items() if not is_empty(v) and not is_molecule(v)}
    for g in gold_rows:
        gs = scorable(g)
        # greedy match to best extracted row
        best_i, best_s = None, -1
        for i, e in enumerate(ext_rows):
            if i in used:
                continue
            s = sum(1 for k, v in gs.items() if k in e and not is_empty(e[k]) and values_match(k, v, e[k], tol))
            s = s / max(1, len(gs))
            if s > best_s:
                best_s, best_i = s, i
        e = ext_rows[best_i] if best_i is not None else {}
        if best_i is not None and best_s > 0:
            used.add(best_i)
        for k, v in gs.items():
            gold_cells += 1
            per_field.setdefault(k, [0, 0]); per_field[k][0] += 1
            if k in e and not is_empty(e[k]) and values_match(k, v, e[k], tol):
                tp += 1; per_field[k][1] += 1
            elif len(examples) < 25:
                examples.append(f"{k}: expected '{v}', got '{e.get(k, '(missing)')}'")
    for e in ext_rows:
        ext_cells += sum(1 for k, v in e.items() if not is_empty(v) and not is_molecule(v))
    return {"tp": tp, "gold": gold_cells, "ext": ext_cells, "perField": per_field, "examples": examples}

def aggregate(scores):
    tp = sum(s["tp"] for s in scores); gc = sum(s["gold"] for s in scores); ec = sum(s["ext"] for s in scores)
    pf = {}
    for s in scores:
        for k, (g, c) in s["perField"].items():
            pf.setdefault(k, [0, 0]); pf[k][0] += g; pf[k][1] += c
    rec = tp / gc if gc else 0.0
    prec = tp / ec if ec else 0.0
    f1 = 2 * prec * rec / (prec + rec) if (prec + rec) else 0.0
    weak = sorted(((k, c / g if g else 0, c, g) for k, (g, c) in pf.items()), key=lambda x: x[1])
    ex = [e for s in scores for e in s["examples"]][:30]
    return {"f1": f1, "precision": prec, "recall": rec, "weak": weak, "examples": ex}

# ---------- OpenAI Responses API ----------
def api_key():
    p = os.path.expanduser("~/.config/chemwiki/openai.key")
    if os.path.isfile(p):
        return open(p).read().strip()
    if os.environ.get("OPENAI_API_KEY"):
        return os.environ["OPENAI_API_KEY"].strip()
    sys.exit("No API key: create ~/.config/chemwiki/openai.key or set $OPENAI_API_KEY")

def post(payload, key, timeout=300):
    req = urllib.request.Request(API, data=json.dumps(payload).encode(),
                                 headers={"Authorization": "Bearer " + key, "Content-Type": "application/json"})
    try:
        with urllib.request.urlopen(req, timeout=timeout) as r:
            return json.load(r)
    except urllib.error.HTTPError as e:
        body = e.read().decode("utf-8", "ignore")
        try:
            msg = json.loads(body)["error"]["message"]
        except Exception:
            msg = body[:500]
        raise RuntimeError(f"HTTP {e.code}: {msg}")

def output_text(resp):
    if resp.get("output_text"):
        return resp["output_text"]
    parts = []
    for item in resp.get("output", []):
        for c in item.get("content", []):
            if c.get("type") in ("output_text", "text") and c.get("text"):
                parts.append(c["text"])
    return "".join(parts)

def schema_for(fields):
    props = {f: {"type": ["string", "null"]} for f in fields}
    item = {"type": "object", "properties": props, "required": list(fields), "additionalProperties": False}
    return {"type": "object",
            "properties": {"summary": {"type": ["string", "null"]}, "experiments": {"type": "array", "items": item}},
            "required": ["summary", "experiments"], "additionalProperties": False}

def split_prompt(text):
    s = "[SYSTEM-LIKE INSTRUCTIONS]"; t = "[TASK]"
    if s in text and t in text:
        sys_part = text.split(s, 1)[1].split(t, 1)[0].strip()
        task = text.split(t, 1)[1].strip()
        return sys_part, task
    return "", text.strip()

def extract(pdf_path, prompt, fields, model, key):
    sys_part, task = split_prompt(prompt)
    data = base64.b64encode(open(pdf_path, "rb").read()).decode()
    payload = {
        "model": model,
        "input": [
            {"role": "developer", "content": [{"type": "input_text", "text": sys_part}]},
            {"role": "user", "content": [
                {"type": "input_file", "filename": os.path.basename(pdf_path),
                 "file_data": "data:application/pdf;base64," + data},
                {"type": "input_text", "text": task},
            ]},
        ],
        "text": {"format": {"type": "json_schema", "name": "extraction", "strict": True, "schema": schema_for(fields)}},
    }
    resp = post(payload, key)
    usage = resp.get("usage", {})
    tokens = usage.get("total_tokens") or (usage.get("input_tokens", 0) + usage.get("output_tokens", 0))
    try:
        d = json.loads(output_text(resp))
        rows = [{k: ("" if v is None else str(v)) for k, v in e.items()} for e in d.get("experiments", [])]
    except Exception:
        rows = []
    return rows, tokens

def write_paper_artifacts(results_dir, hist, best, topic):
    """Final paper artefacts: a markdown summary + a pgfplots/booktabs LaTeX snippet."""
    first, blast = hist[0], hist[-1]
    bagg = best["agg"]
    # summary.md
    md = [f"# Results — {topic.replace('_', ' ')}", "",
          f"- Iterations: {len(hist)}",
          f"- F1: start {first['f1']:.3f} -> best {bagg['f1']:.3f} (iteration {best['iter']}), "
          f"delta {bagg['f1'] - first['f1']:+.3f}",
          f"- Best precision / recall: {bagg['precision']:.3f} / {bagg['recall']:.3f}",
          "", "## Per-field recall (best iteration, worst first)", "",
          "| field | recall | correct/total |", "|---|---|---|"]
    for k, r, c, g in bagg["weak"][:25]:
        md.append(f"| {k} | {r:.2f} | {c}/{g} |")
    open(os.path.join(results_dir, "summary.md"), "w").write("\n".join(md) + "\n")
    # metrics.tex (pgfplots trend + booktabs final table)
    def coords(key):
        return " ".join(f"({r['iteration']},{r[key]:.4f})" for r in hist)
    tex = (f"% trend for {topic}\n\\begin{{tikzpicture}}\n\\begin{{axis}}[xlabel={{Iteration}},"
           f"ylabel={{Score}},ymin=0,ymax=1,legend pos=south east,width=\\linewidth,height=6cm]\n"
           f"\\addplot coordinates {{{coords('f1')}}}; \\addlegendentry{{F1}}\n"
           f"\\addplot coordinates {{{coords('precision')}}}; \\addlegendentry{{Precision}}\n"
           f"\\addplot coordinates {{{coords('recall')}}}; \\addlegendentry{{Recall}}\n"
           f"\\end{{axis}}\n\\end{{tikzpicture}}\n\n"
           f"\\begin{{tabular}}{{lcc}}\n\\hline\nMetric & Iteration 1 & Best (it.\\ {best['iter']}) \\\\\n\\hline\n"
           f"F1 & {first['f1']:.3f} & {bagg['f1']:.3f} \\\\\n"
           f"Precision & {first['precision']:.3f} & {bagg['precision']:.3f} \\\\\n"
           f"Recall & {first['recall']:.3f} & {bagg['recall']:.3f} \\\\\n\\hline\n\\end{{tabular}}\n")
    open(os.path.join(results_dir, "metrics.tex"), "w").write(tex)

def git_commit(paths, msg):
    try:
        subprocess.run(["git", "-C", REPO, "add", "--"] + paths, check=True, capture_output=True)
        subprocess.run(["git", "-C", REPO, "commit", "-q", "-m", msg], check=True, capture_output=True)
        print(f"  committed: {msg}")
    except subprocess.CalledProcessError as e:
        print("  (git commit skipped: " + (e.stderr.decode("utf-8", "ignore")[:120] if e.stderr else "nothing to commit") + ")")

def improve_prompt(current, agg, model, key):
    weak = "\n".join(f"- {k}: recall {r:.2f} ({c}/{g})" for k, r, c, g in agg["weak"][:15])
    ex = "\n- ".join(agg["examples"][:20])
    sys_part = ("You optimize a prompt that extracts experiment data from chemistry papers into a "
                "JSON object {summary, experiments[]}. Keep the exact field names and the JSON "
                "structure; improve only wording, per-field guidance, units, scientific notation, "
                "and row coverage. IMPORTANT: the goal is to fill MORE correct cells — output one "
                "row per distinct experiment and fill every field that the paper states; only leave "
                "a field empty when the value is genuinely absent. Do NOT make the prompt more "
                "conservative or tell the model to omit uncertain values. Respond with the FULL "
                "improved prompt text only.")
    task = (f"[CURRENT METRIC] F1={agg['f1']:.4f} P={agg['precision']:.4f} R={agg['recall']:.4f}\n\n"
            f"[WEAK FIELDS]\n{weak}\n\n[MISMATCH EXAMPLES]\n- {ex}\n\n[CURRENT PROMPT]\n{current}")
    payload = {"model": model, "input": [
        {"role": "developer", "content": [{"type": "input_text", "text": sys_part}]},
        {"role": "user", "content": [{"type": "input_text", "text": task}]}]}
    out = output_text(post(payload, key)).strip()
    m = re.match(r"^```[a-zA-Z]*\s*\n(.*)\n```$", out, re.S)
    return (m.group(1).strip() if m else out) or current

# ---------- main loop ----------
def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--topic", required=True)
    ap.add_argument("--prompt-file", required=True)
    ap.add_argument("--iterations", type=int, default=5)
    ap.add_argument("--limit", type=int, default=0)
    ap.add_argument("--model", default="o3")
    ap.add_argument("--tolerance", type=float, default=0.1)
    ap.add_argument("--export-prompt", action="store_true")
    ap.add_argument("--commit", action="store_true", help="git-commit the results after each iteration (archive the progression)")
    a = ap.parse_args()
    key = api_key()

    gold_dir = os.path.join(EVAL, a.topic, "gold")
    entries = []
    for gj in sorted(glob.glob(os.path.join(gold_dir, "*.json"))):
        d = json.load(open(gj))
        pdf = os.path.join(EVAL, a.topic, d.get("pdf", ""))
        if os.path.isfile(pdf) and d.get("experiments"):
            entries.append((d["doi"], pdf, d["experiments"]))
    if a.limit:
        entries = entries[:a.limit]
    if not entries:
        sys.exit(f"No gold publications with a PDF for topic '{a.topic}'.")
    # field set = union of gold experiment keys (the model is asked to fill exactly these)
    fields = []
    for _, _, exps in entries:
        for e in exps:
            for k in e:
                if k not in fields:
                    fields.append(k)
    print(f"{len(entries)} publication(s), {len(fields)} fields, model={a.model}")

    results_dir = os.path.join(EVAL, a.topic, "results")
    os.makedirs(results_dir, exist_ok=True)
    csv_path = os.path.join(results_dir, "metrics.csv")
    cols = ["iteration", "f1", "f1_best", "precision", "recall", "tokensPerPub"]
    rows_csv = [",".join(cols)]

    prompt = open(a.prompt_file).read().strip()
    best = {"f1": -1, "prompt": prompt, "agg": None, "iter": 0}
    hist = []
    for it in range(1, a.iterations + 1):
        print(f"\n=== iteration {it}/{a.iterations} ===")
        scores, toks = [], 0
        for doi, pdf, gold in entries:
            try:
                rows, t = extract(pdf, prompt, fields, a.model, key)
                toks += t
                s = score_pub(gold, rows, a.tolerance)
                scores.append(s)
                f1p = (2 * (s["tp"] / s["ext"] if s["ext"] else 0) * (s["tp"] / s["gold"] if s["gold"] else 0))
                print(f"  {doi}: matched {s['tp']}/{s['gold']} gold cells, {t} tokens")
            except Exception as e:
                print(f"  {doi}: ERROR {e}")
            time.sleep(1)
        if not scores:
            sys.exit("no publication could be scored")
        agg = aggregate(scores)
        avg_tok = toks // len(scores)
        if agg["f1"] > best["f1"]:
            best = {"f1": agg["f1"], "prompt": prompt, "agg": agg, "iter": it}
        print(f"  AGG F1={agg['f1']:.4f} P={agg['precision']:.4f} R={agg['recall']:.4f} tok/pub={avg_tok} | best={best['f1']:.4f}")
        rows_csv.append(f"{it},{agg['f1']:.4f},{best['f1']:.4f},{agg['precision']:.4f},{agg['recall']:.4f},{avg_tok}")
        hist.append({"iteration": it, "f1": agg["f1"], "f1_best": best["f1"], "precision": agg["precision"], "recall": agg["recall"], "tokensPerPub": avg_tok})
        open(csv_path, "w").write("\n".join(rows_csv) + "\n")
        # regenerate matplotlib trend
        subprocess.run([sys.executable, os.path.join(HERE, "plot_eval_metrics.py"), csv_path, results_dir,
                        a.topic.replace("_", " ")], capture_output=True)
        if a.commit:
            git_commit([results_dir], f"eval {a.topic} iter {it}: F1={agg['f1']:.4f} (best {best['f1']:.4f})")
        if it < a.iterations:
            print("  improving prompt (from best so far) ...")
            # hill-climb: always propose the next variant from the BEST prompt + its error report,
            # so a bad rewrite never drags the search downward.
            prompt = improve_prompt(best["prompt"], best["agg"], a.model, key)

    print(f"\nBest F1: {best['f1']:.4f}")
    open(os.path.join(results_dir, "best_prompt.txt"), "w").write(best["prompt"])
    write_paper_artifacts(results_dir, hist, best, a.topic)
    print(f"Paper artefacts: {results_dir}/trend.pdf, summary.md, metrics.tex, metrics.csv")
    committed_paths = [results_dir]
    if a.export_prompt:
        dest = os.path.join(REPO, "wikischema", "MediaWiki", f"Prompt_import_{a.topic}.wiki")
        open(dest, "w").write(best["prompt"])
        committed_paths.append(dest)
        print(f"Exported best prompt to {dest}")
    if a.commit:
        git_commit(committed_paths, f"eval {a.topic}: final best F1={best['f1']:.4f} + optimized prompt")

if __name__ == "__main__":
    main()
