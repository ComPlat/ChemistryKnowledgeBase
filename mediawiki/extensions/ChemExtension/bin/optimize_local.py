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
FAMILIES["frequency"] = {"h-1": 1.0, "min-1": 60.0, "s-1": 3600.0}
EXPECTED_UNIT = {  # field -> (unit, family)
    "cat conc": ("um", "concentration"), "PS conc": ("mm", "concentration"),
    "e-D conc": ("m", "concentration"), "H-D conc": ("m", "concentration"),
    "host conc": ("m", "concentration"), "guest conc": ("m", "concentration"),
    "λexc": ("nm", "wavelength"), "irr time": ("h", "time"),
    "Quantum_yield__CO": ("%", "percent"),
    "Turnover_frequency__CO": ("h-1", "frequency"), "Turnover_frequency__CH4": ("h-1", "frequency"),
    "Turnover_frequency__H2": ("h-1", "frequency"), "Turnover_frequency__HCOOH": ("h-1", "frequency"),
    "Turnover_frequency__MeOH": ("h-1", "frequency"),
}
# units the model should report each field in (number only) — stated in the prompt to avoid drift
FIELD_UNIT_LABEL = {
    "cat conc": "µM", "PS conc": "mM", "e-D conc": "M", "H-D conc": "M",
    "λexc": "nm", "irr time": "h", "Temperature": "°C",
    "Turnover_frequency__CO": "h^-1", "Turnover_frequency__CH4": "h^-1",
    "Turnover_frequency__H2": "h^-1", "Turnover_frequency__HCOOH": "h^-1", "Turnover_frequency__MeOH": "h^-1",
    "Quantum_yield__CO": "%",
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

def ratio_match(a, b):
    """Order-insensitive ratio compare: '4:1' matches '1:4' (same mixture, convention differs)."""
    ra = re.match(r"^\s*(\d+(?:\.\d+)?)\s*:\s*(\d+(?:\.\d+)?)\s*$", a)
    rb = re.match(r"^\s*(\d+(?:\.\d+)?)\s*:\s*(\d+(?:\.\d+)?)\s*$", b)
    if not (ra and rb):
        return None
    sa = sorted([float(ra[1]), float(ra[2])])
    sb = sorted([float(rb[1]), float(rb[2])])
    return all(abs(x - y) <= 0.02 * max(abs(x), 1.0) for x, y in zip(sa, sb))

def values_match(field, gold, ext, tol=0.1):
    if "ratio" in field.lower():
        r = ratio_match(str(gold), str(ext))
        if r is not None:
            return r
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
    return {"tp": tp, "gold": gold_cells, "ext": ext_cells, "perField": per_field, "examples": examples,
            "gold_rows": len(gold_rows), "ext_rows": len(ext_rows)}

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

def post(payload, key, timeout=300, retries=4):
    body = json.dumps(payload).encode()
    for attempt in range(retries + 1):
        req = urllib.request.Request(API, data=body,
                                     headers={"Authorization": "Bearer " + key, "Content-Type": "application/json"})
        try:
            with urllib.request.urlopen(req, timeout=timeout) as r:
                return json.load(r)
        except urllib.error.HTTPError as e:
            text = e.read().decode("utf-8", "ignore")
            if e.code in (429, 500, 502, 503, 504) and attempt < retries:
                wait = min(60, 5 * (2 ** attempt))
                print(f"    transient HTTP {e.code}, retry in {wait}s ...")
                time.sleep(wait)
                continue
            try:
                msg = json.loads(text)["error"]["message"]
            except Exception:
                msg = text[:500]
            raise RuntimeError(f"HTTP {e.code}: {msg}")
        except (urllib.error.URLError, TimeoutError) as e:
            if attempt < retries:
                wait = min(60, 5 * (2 ** attempt))
                print(f"    network error ({e}), retry in {wait}s ...")
                time.sleep(wait)
                continue
            raise RuntimeError(f"network error: {e}")

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

def build_field_examples(entries):
    """field -> {doi -> [values]} for leakage-free few-shot format hints."""
    fd = {}
    for doi, _, exps in entries:
        for e in exps:
            for k, v in e.items():
                if is_empty(v) or is_molecule(v):
                    continue
                fd.setdefault(k, {}).setdefault(doi, []).append(str(v))
    return fd

def field_hints(fd, skip_doi, fields, per_field=4):
    """Example values per field taken from OTHER papers (never the current one)."""
    lines = []
    for f in fields:
        vals = []
        for doi, vs in fd.get(f, {}).items():
            if doi == skip_doi:
                continue
            for v in vs:
                if v not in vals:
                    vals.append(v)
        if vals:
            lines.append(f"- {f}: e.g. " + " | ".join(vals[:per_field]))
    return "\n".join(lines)

def parse_csv_rows(text):
    """Parse the ```csv (or <pre>) experiment table out of a response; mirrors the live importer.
    Picks the table with the most rows; strips [unit] hints from headers."""
    tables = re.findall(r"(?:```csv|<pre>)(.*?)(?:```|</pre>)", text, re.S)
    best = []
    for b in tables:
        lines = [l.strip() for l in b.strip().split("\n") if l.strip()]
        if not lines:
            continue
        header = [re.sub(r"\[[^\]]*\]", "", h).strip() for h in lines[0].split(",")]
        rows = []
        for ln in lines[1:]:
            cols = [c.strip() for c in ln.split(",")]
            while len(header) > len(cols):
                cols.append("")
            cols = cols[:len(header)]
            rows.append({h: v for h, v in zip(header, cols)})
        if len(rows) > len(best):
            best = rows
    return best

def extract(pdf_path, prompt, fields, model, key, hints="", fmt="json"):
    sys_part, task = split_prompt(prompt)
    units = [f"{f}={FIELD_UNIT_LABEL[f]}" for f in fields if f in FIELD_UNIT_LABEL]
    if units:
        task += ("\n\n[FIELD UNITS] Report these fields as a bare number in EXACTLY these units "
                 "(convert if the paper uses another unit; no unit text in the value): " + "; ".join(units))
    if hints:
        task += ("\n\n[FIELD FORMAT EXAMPLES — these are example value formats from OTHER papers, "
                 "NOT the answers for this paper; use them only to understand each column]\n" + hints)
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
    }
    if fmt == "json":
        payload["text"] = {"format": {"type": "json_schema", "name": "extraction", "strict": True, "schema": schema_for(fields)}}
    resp = post(payload, key)
    usage = resp.get("usage", {})
    tokens = usage.get("total_tokens") or (usage.get("input_tokens", 0) + usage.get("output_tokens", 0))
    out = output_text(resp)
    if fmt == "json":
        try:
            d = json.loads(out)
            rows = [{k: ("" if v is None else str(v)) for k, v in e.items()} for e in d.get("experiments", [])]
        except Exception:
            rows = []
    else:
        rows = parse_csv_rows(out)
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

def ground_check(pdf_path, ext_rows, model, key):
    """Verify each non-empty extracted cell against the source PDF (faithfulness / no-hallucination).
    Returns supported/contradicted/absent counts + a few unsupported examples."""
    cells = []
    for i, row in enumerate(ext_rows):
        for k, v in row.items():
            if not is_empty(v) and not is_molecule(v):
                cells.append({"row": i, "field": k, "value": str(v)})
    if not cells:
        return {"checked": 0, "supported": 0, "unsupported": 0, "examples": []}
    data = base64.b64encode(open(pdf_path, "rb").read()).decode()
    schema = {"type": "object", "properties": {"checks": {"type": "array", "items": {
        "type": "object", "properties": {
            "row": {"type": "integer"}, "field": {"type": "string"},
            "status": {"type": "string", "enum": ["supported", "contradicted", "absent"]},
            "evidence": {"type": ["string", "null"]}},
        "required": ["row", "field", "status", "evidence"], "additionalProperties": False}}},
        "required": ["checks"], "additionalProperties": False}
    instr = ("Check each of the following values that were extracted from the attached paper. For "
             "each, decide if the paper SUPPORTS it (verbatim or directly derivable), CONTRADICTS "
             "it, or the value is ABSENT from the paper. Give a short verbatim quote as evidence "
             "for 'supported'/'contradicted'. Be strict: a value not clearly in the paper is "
             "'absent'.")
    payload = {"model": model, "input": [
        {"role": "developer", "content": [{"type": "input_text", "text": instr}]},
        {"role": "user", "content": [
            {"type": "input_file", "filename": os.path.basename(pdf_path), "file_data": "data:application/pdf;base64," + data},
            {"type": "input_text", "text": "Values (JSON):\n" + json.dumps(cells, ensure_ascii=False)}]}],
        "text": {"format": {"type": "json_schema", "name": "grounding", "strict": True, "schema": schema}}}
    try:
        d = json.loads(output_text(post(payload, key)))
        checks = d.get("checks", [])
    except Exception:
        checks = []
    supported = sum(1 for c in checks if c.get("status") == "supported")
    unsupported = sum(1 for c in checks if c.get("status") in ("contradicted", "absent"))
    ex = [f"{c['field']}='{cells[c['row']]['value'] if c.get('row',0) < len(cells) else '?'}' [{c.get('status')}]"
          for c in checks if c.get("status") in ("contradicted", "absent")][:15]
    return {"checked": supported + unsupported, "supported": supported, "unsupported": unsupported, "examples": ex}

def improve_prompt(current, agg, model, key, fmt="json"):
    weak = "\n".join(f"- {k}: recall {r:.2f} ({c}/{g})" for k, r, c, g in agg["weak"][:15])
    ex = "\n- ".join(agg["examples"][:20])
    struct = ("a fenced ```csv block whose header is EXACTLY the given columns (plus the prose "
              "sections as MediaWiki text above it)" if fmt == "csv"
              else "a JSON object {summary, experiments[]}")
    sys_part = ("You optimize a prompt that extracts experiment data from chemistry papers into "
                + struct + ". Keep the exact column/field names and the output structure unchanged; "
                "improve only wording, per-field guidance, units, scientific notation, and row "
                "coverage. IMPORTANT: the goal is to fill MORE correct cells — output one row per "
                "distinct experiment and fill every field that the paper states; only leave a field "
                "empty when the value is genuinely absent. Do NOT make the prompt more conservative "
                "or tell the model to omit uncertain values. Respond with the FULL improved prompt "
                "text only.")
    ground = ""
    if agg.get("groundedness") is not None:
        ground = (f"Groundedness={agg['groundedness']:.3f} Hallucination={agg['hallucination']:.3f} "
                  f"(every value MUST be supported by the paper; never invent or guess values).\n")
    task = (f"[CURRENT METRIC] F1={agg['f1']:.4f} P={agg['precision']:.4f} R={agg['recall']:.4f}\n{ground}\n"
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
    ap.add_argument("--format", choices=["json", "csv"], default="json",
                    help="json = structured output; csv = produce a ```csv table (matches the live wiki import)")
    ap.add_argument("--tolerance", type=float, default=0.1)
    ap.add_argument("--export-prompt", action="store_true")
    ap.add_argument("--ground", action="store_true", help="verify each extracted value against the PDF (groundedness / no-hallucination)")
    ap.add_argument("--field-hints", action="store_true", help="give the model example value formats per field, sampled from OTHER gold papers (leakage-free)")
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
    fd = build_field_examples(entries) if a.field_hints else {}
    print(f"{len(entries)} publication(s), {len(fields)} fields, model={a.model}"
          + (", field-hints on" if a.field_hints else ""))

    results_dir = os.path.join(EVAL, a.topic, "results")
    os.makedirs(results_dir, exist_ok=True)
    csv_path = os.path.join(results_dir, "metrics.csv")
    cols = ["iteration", "f1", "f1_best", "precision", "recall", "groundedness", "hallucination", "tokensPerPub"]
    rows_csv = [",".join(cols)]

    prompt = open(a.prompt_file).read().strip()
    if a.format == "csv":
        # bake the exact CSV column contract into the prompt so the EXPORTED prompt is
        # live-deployable (the wiki importer maps these headers to template parameters)
        prompt += ("\n\n[OUTPUT FORMAT] First write the prose summary sections as MediaWiki text. "
                   "Then output the experiments as ONE fenced code block that starts with ```csv and "
                   "ends with ```. The header row MUST be EXACTLY these columns, in this order:\n"
                   + " , ".join(fields) + "\nOne row per distinct experiment; one value per cell; "
                   "leave a cell empty only if the paper does not state it.")
    best = {"f1": -1, "prompt": prompt, "agg": None, "iter": 0}
    hist = []
    for it in range(1, a.iterations + 1):
        print(f"\n=== iteration {it}/{a.iterations} ===")
        scores, toks, diag = [], 0, []
        gsup = gchk = 0
        for doi, pdf, gold in entries:
            try:
                hints = field_hints(fd, doi, fields) if a.field_hints else ""
                rows, t = extract(pdf, prompt, fields, a.model, key, hints, a.format)
                toks += t
                s = score_pub(gold, rows, a.tolerance)
                scores.append(s)
                line = f"  {doi}: matched {s['tp']}/{s['gold']} gold cells, rows {s['ext_rows']}/{s['gold_rows']}, {t} tokens"
                grec = None
                if a.ground:
                    g = ground_check(pdf, rows, a.model, key)
                    gsup += g["supported"]; gchk += g["checked"]
                    grec = g
                    line += f", grounded {g['supported']}/{g['checked']}"
                print(line)
                diag.append({"doi": doi, "tp": s["tp"], "gold_cells": s["gold"], "ext_cells": s["ext"],
                             "gold_rows": s["gold_rows"], "ext_rows": s["ext_rows"],
                             "ground": grec, "examples": s["examples"][:10]})
            except Exception as e:
                print(f"  {doi}: ERROR {e}")
            time.sleep(1)
        if not scores:
            sys.exit("no publication could be scored")
        agg = aggregate(scores)
        agg["groundedness"] = (gsup / gchk) if (a.ground and gchk) else None
        agg["hallucination"] = (1 - gsup / gchk) if (a.ground and gchk) else None
        avg_tok = toks // len(scores)
        # objective: gold-F1, and when grounding is on, reward faithfulness + punish hallucination
        agg["objective"] = agg["f1"] if not a.ground or agg["groundedness"] is None \
            else (agg["f1"] + agg["groundedness"]) / 2
        if agg["objective"] > best.get("obj", -1):
            best = {"f1": agg["f1"], "obj": agg["objective"], "prompt": prompt, "agg": agg, "iter": it}
        gstr = f" grounded={agg['groundedness']:.3f} halluc={agg['hallucination']:.3f}" if agg["groundedness"] is not None else ""
        print(f"  AGG F1={agg['f1']:.4f} P={agg['precision']:.4f} R={agg['recall']:.4f}{gstr} tok/pub={avg_tok} | best={best['f1']:.4f}")
        gv = f"{agg['groundedness']:.4f}" if agg["groundedness"] is not None else ""
        hv = f"{agg['hallucination']:.4f}" if agg["hallucination"] is not None else ""
        rows_csv.append(f"{it},{agg['f1']:.4f},{best['f1']:.4f},{agg['precision']:.4f},{agg['recall']:.4f},{gv},{hv},{avg_tok}")
        hist.append({"iteration": it, "f1": agg["f1"], "f1_best": best["f1"], "precision": agg["precision"], "recall": agg["recall"], "tokensPerPub": avg_tok})
        json.dump({"iteration": it, "aggregate": {k: agg[k] for k in ("f1", "precision", "recall", "groundedness", "hallucination")},
                   "weak_fields": [{"field": k, "recall": r, "correct": c, "total": g} for k, r, c, g in agg["weak"]],
                   "per_publication": diag},
                  open(os.path.join(results_dir, "diagnostics.json"), "w"), indent=2, ensure_ascii=False)
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
            prompt = improve_prompt(best["prompt"], best["agg"], a.model, key, a.format)

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
