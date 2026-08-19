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
import argparse, base64, glob, json, math, os, re, subprocess, sys, time, urllib.request, urllib.error

HERE = os.path.dirname(os.path.abspath(__file__))
EXT = os.path.dirname(HERE)                       # .../ChemExtension
EVAL = os.path.join(EXT, "eval")
REPO = os.path.dirname(os.path.dirname(os.path.dirname(EXT)))  # repo root
API = "https://api.openai.com/v1/responses"
API_ANTHROPIC = "https://api.anthropic.com/v1/messages"

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

# ---------- layout / structure scoring ----------
# Required output sections per topic. The live wiki publication pages are built from these,
# so the optimizer is not allowed to drop them. Override via eval/<topic>/profile.json
# ("required_sections": [...]). Empty list disables the layout check entirely.
REQUIRED_SECTIONS_BY_TOPIC = {
    "Photocatalytic_CO2_conversion": [
        "Abstract Summary",
        "Advances and Special Progress",
        "Additional Remarks",
        "Content of the Published Article in Detail",
        "Catalyst",
        "Photosensitizer",
        "Investigation",
    ],
    "Electrochemical_CO2_conversion": [
        "Abstract Summary", "Advances and Special Progress", "Additional Remarks",
        "Content of the Published Article in Detail", "Catalyst", "Investigation",
    ],
    "Host_Guest_interaction": [
        "Abstract Summary", "Advances and Special Progress", "Additional Remarks",
        "Content of the Published Article in Detail", "Host", "Guest", "Investigation",
    ],
}
# minimum substantive words between a section heading and the next required heading
MIN_SECTION_WORDS = 20
# soft upper bound per prose section (Investigation exempt). Above this, the conciseness
# score starts to shrink linearly and penalises bloat. Keeps the wiki pages "kurz und prägnant".
MAX_SECTION_WORDS = 120

# log10 of known cross-family conversion factors used to flag likely unit errors when the
# extracted numeric is off by exactly a family factor from the gold value. Mirrors the units
# already handled in FAMILIES (concentration µM/mM/M: ×1000; time h/min/s: ×60 / ×3600;
# frequency h⁻¹/min⁻¹/s⁻¹: ×60 / ×3600). Ratios within UNIT_LOG_TOL of any of these are
# classified as unit confusion, not chemistry errors.
UNIT_FACTORS_LOG = [
    3.0, -3.0,                              # ×1000 (µM↔mM, mM↔M)
    math.log10(60.0), -math.log10(60.0),    # ×60   (h↔min, min↔s)
    math.log10(3600.0), -math.log10(3600.0),# ×3600 (h↔s)
]
UNIT_LOG_TOL = 0.05  # absorbs the existing ±10% numeric tolerance in log space

def _heading_positions(text, sections):
    """Return ordered list of (section_name, start_index_after_heading). Matches wiki
    headings (= ... =, == ... ==), markdown headings (#, ##), bracketed prompt-style
    titles ([Name]), or the bare name on its own line. Case-insensitive."""
    hits = []
    for name in sections:
        pat = re.compile(
            r"(?im)^\s*(?:=+\s*|#+\s*|\[\s*)?" + re.escape(name) + r"(?:\s*=+|\s*\]|\s*:)?\s*$",
            re.MULTILINE,
        )
        m = pat.search(text)
        if m:
            hits.append((name, m.end()))
    hits.sort(key=lambda x: x[1])
    return hits

def layout_score(text, sections):
    """Per-publication structure score on the raw model output.
    Returns {score: 0..1, present: int, required: int, missing: [names]}.
    A section counts as present only if it has at least MIN_SECTION_WORDS of substance
    after its heading and (for Investigation) a fenced ```csv block.
    """
    required = list(sections or [])
    if not required:
        return {"score": 1.0, "present": 0, "required": 0, "missing": []}
    positions = _heading_positions(text or "", required)
    by_name = {n: pos for n, pos in positions}
    ordered_offsets = sorted(p for _, p in positions) + [len(text or "")]
    present, missing = 0, []
    for name in required:
        if name not in by_name:
            missing.append(name); continue
        start = by_name[name]
        # find next heading offset that's strictly after `start`
        next_off = next((o for o in ordered_offsets if o > start), len(text or ""))
        body = (text or "")[start:next_off]
        words = re.findall(r"\b\w[\w\-]*\b", body)
        substantive = len(words) >= MIN_SECTION_WORDS
        if name.lower() == "investigation":
            # Investigation is a DATA section; word-count doesn't apply. We require a fenced
            # csv block plus at least header + 1 data row.
            has_csv = "```csv" in body or re.search(r"```\s*\ncatalyst\b", body) is not None
            csv_rows = sum(1 for line in body.splitlines() if line.count(",") >= 3)
            if has_csv and csv_rows >= 2:
                present += 1
            else:
                missing.append(name)
        elif substantive:
            present += 1
        else:
            missing.append(name)
    return {"score": present / len(required), "present": present,
            "required": len(required), "missing": missing}

def _section_bodies(text, sections):
    """Slice `text` into (name, body_str) pairs following the same heading rules as
    layout_score. Sections missing from `text` are skipped rather than returned empty."""
    positions = _heading_positions(text or "", sections or [])
    by_name = {n: pos for n, pos in positions}
    ordered_offsets = sorted(p for _, p in positions) + [len(text or "")]
    out = []
    for name in sections or []:
        if name not in by_name:
            continue
        start = by_name[name]
        next_off = next((o for o in ordered_offsets if o > start), len(text or ""))
        out.append((name, (text or "")[start:next_off]))
    return out

def conciseness_score(text, sections):
    """Two-axis conciseness metric on the raw model output.
    Axis A — verbosity: mean over-length fraction per prose section, capped at 1.0.
             A section of 240 words with MAX=120 → over = (240-120)/120 = 1.0.
    Axis B — redundancy: mean pairwise trigram Jaccard between prose sections.
             Two identical sections → 1.0; disjoint → 0.0.
    Combined: conciseness = 1 - clip(0.5*verbosity + 0.5*redundancy, 0, 1) in [0, 1].
    Investigation (data) is exempt on both axes. Returns
    {score, verbosity, redundancy, over_sections: [(name, words)]}.
    """
    prose_sections = [s for s in (sections or []) if s.lower() != "investigation"]
    if not prose_sections:
        return {"score": 1.0, "verbosity": 0.0, "redundancy": 0.0, "over_sections": []}
    bodies = _section_bodies(text or "", prose_sections)
    if not bodies:
        # nothing to measure (layout_score already flags missing sections)
        return {"score": 1.0, "verbosity": 0.0, "redundancy": 0.0, "over_sections": []}
    over_pen, over_sections = [], []
    tri_sets = []
    for name, body in bodies:
        words = re.findall(r"\b\w[\w\-]*\b", body.lower())
        n = len(words)
        over = max(0.0, (n - MAX_SECTION_WORDS) / MAX_SECTION_WORDS)
        over_pen.append(min(1.0, over))
        if n > MAX_SECTION_WORDS:
            over_sections.append((name, n))
        # trigrams for redundancy
        tri_sets.append(set(zip(words, words[1:], words[2:])) if n >= 3 else set())
    verbosity = sum(over_pen) / len(over_pen)
    # pairwise Jaccard between non-empty sections; ignore empty sets to avoid division noise
    pairs, sim_sum = 0, 0.0
    for i in range(len(tri_sets)):
        for j in range(i + 1, len(tri_sets)):
            a, b = tri_sets[i], tri_sets[j]
            if not a and not b:
                continue
            union = len(a | b)
            if union == 0:
                continue
            sim_sum += len(a & b) / union
            pairs += 1
    redundancy = (sim_sum / pairs) if pairs else 0.0
    raw_penalty = 0.5 * verbosity + 0.5 * redundancy
    score = 1.0 - max(0.0, min(1.0, raw_penalty))
    return {"score": score, "verbosity": verbosity, "redundancy": redundancy,
            "over_sections": over_sections}

def is_unit_error(ext_val, gold_val):
    """Cheap post-hoc detector: numeric values whose ratio matches a known cross-family
    conversion factor (×1000 for µM/mM/M, ×60 or ×3600 for time/frequency) are almost
    certainly unit confusions on the extractor's side rather than chemistry mistakes.
    Returns (True, factor) or (False, None). Values of 0, missing, or non-numeric → False.
    """
    if ext_val is None or gold_val is None:
        return False, None
    a, b = abs(float(ext_val)), abs(float(gold_val))
    if a <= 0 or b <= 0:
        return False, None
    log_r = math.log10(a / b)
    for k in UNIT_FACTORS_LOG:
        if abs(log_r - k) < UNIT_LOG_TOL:
            return True, round(10 ** k, 4)
    return False, None

# ---------- diff operators (structured prompt edits) ----------
# Deterministic, idempotent, marker-guarded edits to an extraction prompt.
# The meta-LLM chooses which operator + args to apply; the code applies it.
# Small, testable deltas replace the previous free-form rewrite (11 000-char action
# space) that had a catastrophic bias to reduce F1 while "fixing" one signal.
_OPS_SECTION_HEADER = "<!-- ===== OPERATOR-DIFFS (applied by the optimization loop) ===== -->"

def _ensure_ops_section(prompt):
    if _OPS_SECTION_HEADER in prompt:
        return prompt
    return prompt.rstrip() + "\n\n" + _OPS_SECTION_HEADER + "\n"

def _append_op(prompt, marker, body):
    """Append `body` under `marker` to the ops section. No-op if marker already present."""
    prompt = _ensure_ops_section(prompt)
    if marker in prompt:
        return prompt
    return prompt.rstrip() + "\n" + marker + "\n" + body + "\n"

def op_add_field_example(prompt, field, example):
    """Insert a concrete example value for a weak field."""
    marker = f"<!-- OP:add_field_example {field} -->"
    body = (f"Example value for the '{field}' cell: {example}. Follow this exact format "
            f"(number only, canonical unit, no unit symbol in the cell).")
    return _append_op(prompt, marker, body)

def op_strengthen_recall(prompt, field):
    """Add a hard MUST-fill instruction for a field with chronically low recall."""
    marker = f"<!-- OP:strengthen_recall {field} -->"
    body = (f"CRITICAL RECALL: For the field '{field}', you MUST fill the value whenever the "
            f"paper reports it — including figure captions, footnotes, tables, and Supporting "
            f"Information. Only leave the cell empty if the paper genuinely does not state it.")
    return _append_op(prompt, marker, body)

def op_add_unit_rule(prompt, family, source, target, factor):
    """Add an explicit canonical-unit conversion rule."""
    marker = f"<!-- OP:add_unit_rule {family} {source}->{target} -->"
    factor = float(factor)
    if factor >= 1.0:
        op_symbol, abs_factor = "÷", factor
    else:
        op_symbol, abs_factor = "×", 1.0 / factor
    body = (f"UNIT CONVERSION for {family}: if the paper reports the value in {source}, "
            f"convert to {target} by {op_symbol}{abs_factor:g} BEFORE writing the CSV cell. "
            f"Emit the numeric value in {target} only — no unit symbol in the cell. "
            f"This is a CONVERSION rule; never drop the cell because the unit is unclear.")
    return _append_op(prompt, marker, body)

def op_tighten_section(prompt, section, max_words):
    """Add a hard word budget for a specific prose section."""
    marker = f"<!-- OP:tighten {section} {max_words} -->"
    body = (f"WORD BUDGET: The '{section}' section MUST be at most {int(max_words)} words. "
            f"Prefer short substantive sentences. Cut restatements and filler; keep facts, "
            f"catalyst names, numeric ranges, mechanistic claims.")
    return _append_op(prompt, marker, body)

def op_dedupe_trigrams(prompt):
    """Add an explicit no-cross-section-repetition rule."""
    marker = "<!-- OP:dedupe_trigrams -->"
    body = ("CROSS-SECTION DE-DUPLICATION: Do not repeat the same three-word phrase across "
            "multiple prose sections. If a claim is stated in one section, do not restate it "
            "in another section — reference it by context instead.")
    return _append_op(prompt, marker, body)

def op_insert_row_coverage_hint(prompt, pattern):
    """Add a row-coverage instruction with a descriptive pattern hint."""
    pattern_hash = str(abs(hash(pattern)))[:8]
    marker = f"<!-- OP:row_coverage {pattern_hash} -->"
    body = (f"ROW COVERAGE: Emit ONE row per distinct experiment. Ensure separate rows for "
            f"each unique combination of catalyst, additive, solvent, and light source described "
            f"in the paper. Guidance pattern: {pattern}")
    return _append_op(prompt, marker, body)

def op_no_op(prompt):
    """Deliberate no-op — the designer decided no change is warranted this iteration."""
    return prompt

DIFF_OPERATORS = {
    "add_field_example": op_add_field_example,
    "strengthen_recall": op_strengthen_recall,
    "add_unit_rule": op_add_unit_rule,
    "tighten_section": op_tighten_section,
    "dedupe_trigrams": op_dedupe_trigrams,
    "insert_row_coverage_hint": op_insert_row_coverage_hint,
    "no_op": op_no_op,
}

def apply_diff(prompt, op_choice):
    """Dispatch an operator choice against the registry. Returns the unchanged prompt on
    unknown operator name or argument-mismatch — defensive against meta-LLM hallucinations."""
    if not isinstance(op_choice, dict):
        return prompt
    op_name = op_choice.get("operator", "no_op")
    args = op_choice.get("args", {}) or {}
    fn = DIFF_OPERATORS.get(op_name)
    if fn is None:
        return prompt
    try:
        return fn(prompt, **args)
    except TypeError:
        # arg mismatch (missing/extra) — treat as no-op rather than crash
        return prompt

def composite_objective(f1, avg_layout, avg_conciseness=None):
    """Multiplicative gate with a soft floor for conciseness:
        composite = F1 × Layout × (0.5 + 0.5 × Conciseness)
    Layout is a HARD gate (missing sections shrink the score linearly — a page with 0/7
    sections is worth nothing to the wiki). Conciseness is a SOFT gate — even fully
    bloated prose still lets 50 % of F1 through, so the optimizer can never win by
    aggressively cutting cells to hit conciseness=1.0 at the cost of extraction quality.
    conciseness defaults to 1.0 for backwards compatibility (e.g., JSON output has no prose).
    """
    layout = 1.0 if avg_layout is None else avg_layout
    conc = 1.0 if avg_conciseness is None else avg_conciseness
    return f1 * layout * (0.5 + 0.5 * conc)

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
    # per_field[k] = [gold_count, correct_count, unit_error_count] — unit errors are a
    # subset of the misses that look like a family-factor swap (µM/mM etc.), tracked
    # so the meta-LLM can see systematic unit confusions as actionable feedback.
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
            per_field.setdefault(k, [0, 0, 0]); per_field[k][0] += 1
            if k in e and not is_empty(e[k]) and values_match(k, v, e[k], tol):
                tp += 1; per_field[k][1] += 1
            else:
                # miss — check if it looks like a unit-family swap and mark the example
                unit_flag = ""
                if k in e and not is_empty(e[k]):
                    gn = parse_num(v); en = parse_num(e[k])
                    is_ue, factor = is_unit_error(en, gn)
                    if is_ue:
                        per_field[k][2] += 1
                        unit_flag = f"  [UNIT_ERROR: ext/gold ≈ {factor:g}]"
                if len(examples) < 25:
                    examples.append(f"{k}: expected '{v}', got '{e.get(k, '(missing)')}'" + unit_flag)
    for e in ext_rows:
        ext_cells += sum(1 for k, v in e.items() if not is_empty(v) and not is_molecule(v))
    return {"tp": tp, "gold": gold_cells, "ext": ext_cells, "perField": per_field, "examples": examples,
            "gold_rows": len(gold_rows), "ext_rows": len(ext_rows)}

def aggregate(scores):
    tp = sum(s["tp"] for s in scores); gc = sum(s["gold"] for s in scores); ec = sum(s["ext"] for s in scores)
    pf = {}
    for s in scores:
        for k, ent in s["perField"].items():
            # tolerate old 2-tuple perField entries (backwards compat with cached test data)
            g, c = ent[0], ent[1]
            u = ent[2] if len(ent) > 2 else 0
            pf.setdefault(k, [0, 0, 0]); pf[k][0] += g; pf[k][1] += c; pf[k][2] += u
    rec = tp / gc if gc else 0.0
    prec = tp / ec if ec else 0.0
    f1 = 2 * prec * rec / (prec + rec) if (prec + rec) else 0.0
    # weak: (field, recall, correct, gold_count, unit_errors, misses)
    weak = sorted(
        ((k, (c / g if g else 0), c, g, u, max(0, g - c)) for k, (g, c, u) in pf.items()),
        key=lambda x: x[1],
    )
    ex = [e for s in scores for e in s["examples"]][:30]
    # unitCorrectness in [0,1]: 1 = no misses look like family-factor swaps.
    total_misses = sum(max(0, g - c) for g, c, _ in pf.values())
    total_unit_errors = sum(u for _, _, u in pf.values())
    unit_correctness = 1.0 - (total_unit_errors / total_misses) if total_misses else 1.0
    return {"f1": f1, "precision": prec, "recall": rec, "weak": weak, "examples": ex,
            "unitCorrectness": unit_correctness}

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

def post_anthropic(payload, key, timeout=300, retries=4):
    """Anthropic Messages API caller — analog to `post` but for claude-* designer-model.
    Used only from choose_operator when a --designer-model claude-* is selected."""
    body = json.dumps(payload).encode()
    headers = {
        "x-api-key": key,
        "anthropic-version": "2023-06-01",
        "content-type": "application/json",
    }
    for attempt in range(retries + 1):
        req = urllib.request.Request(API_ANTHROPIC, data=body, headers=headers)
        try:
            with urllib.request.urlopen(req, timeout=timeout) as r:
                return json.load(r)
        except urllib.error.HTTPError as e:
            text = e.read().decode("utf-8", "ignore")
            if e.code in (429, 500, 502, 503, 504) and attempt < retries:
                wait = min(60, 5 * (2 ** attempt))
                print(f"    transient anthropic HTTP {e.code}, retry in {wait}s ...")
                time.sleep(wait)
                continue
            try:
                msg = json.loads(text).get("error", {}).get("message", text[:500])
            except Exception:
                msg = text[:500]
            raise RuntimeError(f"anthropic HTTP {e.code}: {msg}")
        except (urllib.error.URLError, TimeoutError) as e:
            if attempt < retries:
                wait = min(60, 5 * (2 ** attempt))
                print(f"    anthropic network error ({e}), retry in {wait}s ...")
                time.sleep(wait)
                continue
            raise RuntimeError(f"anthropic network error: {e}")

def _anthropic_text(resp):
    """Extract text from an Anthropic Messages API response."""
    content = resp.get("content") or []
    for block in content:
        if isinstance(block, dict) and block.get("type") == "text":
            return block.get("text", "")
    return ""

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
    return rows, tokens, out

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
          "| field | recall | correct/total | unit-errors |", "|---|---|---|---|"]
    for entry in bagg["weak"][:25]:
        # tolerate both old 4-tuple and new 6-tuple weak entries
        k, r, c, g = entry[0], entry[1], entry[2], entry[3]
        u = entry[4] if len(entry) > 4 else 0
        m = entry[5] if len(entry) > 5 else max(0, g - c)
        ue_cell = f"{u}/{m} ({(u/m*100):.0f}%)" if m else "—"
        md.append(f"| {k} | {r:.2f} | {c}/{g} | {ue_cell} |")
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

def generate_initial_prompt(fields, fmt, model, key, required_sections=None, gold_example=None):
    """Cold start: no seed prompt given — let the model write the initial extraction prompt itself."""
    cols = " , ".join(fields)
    section_clause = ""
    if required_sections:
        section_clause = (
            " The prompt MUST require the extractor to emit ALL of these MediaWiki sections in this "
            "exact order, each as a wiki heading FOLLOWED BY substantive paper-specific content "
            "(at least 30 substantive words per section, never placeholder text like '<...>' or "
            "'(...)' — the extractor treats those as empty and skips the section). List of sections: "
            + ", ".join(required_sections)
            + ". For each section the generated prompt must include CONCRETE PER-SECTION "
            "INSTRUCTIONS describing what content belongs there (e.g. for 'Catalyst': "
            "'describe the catalyst family, active metal, ligand structure, and key structural "
            "features'). Do NOT use placeholder phrases like '<compact narrative>' or "
            "'<concise description>' — the extractor will emit them literally and lose the "
            "section. The Investigation section must contain the experiments inside a fenced "
            "```csv block whose first row is the column header."
        )
    sys_part = ("Write a concise, high-quality instruction prompt that makes a model extract the "
                "experimental data from an attached chemistry paper. The prompt should ask for a "
                "short MediaWiki summary followed by the experiments as structured data, ONE row "
                "per experiment, using ONLY values explicitly stated in the paper (no guessing, no "
                "hallucination)." + section_clause + " Respond with the prompt text only.")
    task = "The experiments to capture have these fields:\n" + cols
    if gold_example:
        task += ("\n\n[GOLD STANDARD EXAMPLE — the desired output structure looks like this. "
                 "Mimic its sectioning, prose density, and CSV layout exactly]\n"
                 + gold_example
                 + "\n[END GOLD STANDARD EXAMPLE]")
    payload = {"model": model, "input": [
        {"role": "developer", "content": [{"type": "input_text", "text": sys_part}]},
        {"role": "user", "content": [{"type": "input_text", "text": task}]}]}
    return output_text(post(payload, key)).strip()

def build_memory(hist):
    """Cross-iteration pattern accumulator for the designer-mode meta-LLM.
    Turns per-iteration diagnostics into stable failure patterns so the designer
    can address the WHOLE run's weaknesses at once, not just the last snapshot.
    Returns None on the first iteration (no history yet)."""
    if not hist:
        return None
    field_recalls, field_unit_errs, section_max_words = {}, {}, {}
    for h in hist:
        for entry in h.get("weak_fields_raw", []):
            f = entry["field"]
            field_recalls.setdefault(f, []).append(entry["recall"])
            field_unit_errs[f] = field_unit_errs.get(f, 0) + entry.get("unit_errors", 0)
        for name, words in h.get("over_sections", []) or []:
            section_max_words[name] = max(section_max_words.get(name, 0), words)
    # a field is "persistently weak" if its MEAN recall across all iters < 0.30
    persistent_weak = sorted(
        ((f, sum(rs) / len(rs), len(rs)) for f, rs in field_recalls.items() if sum(rs) / len(rs) < 0.30),
        key=lambda x: x[1],
    )[:12]
    # a field has a systematic unit-family problem if unit errors accumulate to >= 10
    persistent_unit_errs = sorted(
        ((f, u) for f, u in field_unit_errs.items() if u >= 10),
        key=lambda x: -x[1],
    )[:6]
    # sections that went over-length in any iteration (top 5 by max words)
    persistent_bloat = sorted(section_max_words.items(), key=lambda x: -x[1])[:5]
    return {
        "iterations_seen": len(hist),
        "persistent_weak_fields": persistent_weak,
        "persistent_unit_error_fields": persistent_unit_errs,
        "persistent_over_sections": persistent_bloat,
    }

# Focus rotation for design-mode: each iteration targets ONE quality axis so the
# meta-LLM never trades F1 for polish. Iter 1 is the seed measurement (no rewrite).
FOCUS_SCHEDULE = {
    2: "recall",
    3: "recall",
    4: "conciseness",
    5: "units",
    6: "polish",
}

def improve_prompt(current, agg, model, key, fmt="json", required_sections=None,
                   gold_example=None, memory=None, focus="polish"):
    # weak entries carry (field, recall, correct, gold, unit_errors, misses). When
    # unit-family confusion dominates the misses (>20%), spell it out so the meta-LLM
    # doesn't just see low recall but sees "µM↔mM confusion" as the actionable cause.
    weak_lines = []
    for entry in agg["weak"][:15]:
        k, r, c, g = entry[0], entry[1], entry[2], entry[3]
        u = entry[4] if len(entry) > 4 else 0
        m = entry[5] if len(entry) > 5 else max(0, g - c)
        line = f"- {k}: recall {r:.2f} ({c}/{g})"
        if m and u / m > 0.2:
            line += f", {u}/{m} misses look like UNIT-FAMILY confusion (wrong µM/mM/M or h/min/s)"
        weak_lines.append(line)
    weak = "\n".join(weak_lines)
    ex = "\n- ".join(agg["examples"][:20])
    struct = ("a fenced ```csv block whose header is EXACTLY the given columns (plus the prose "
              "sections as MediaWiki text above it)" if fmt == "csv"
              else "a JSON object {summary, experiments[]}")
    sect = ""
    if required_sections:
        sect = ("The output structure has FIXED MediaWiki sections that MUST all be emitted with "
                "substantive content (each at least 20 words, target ≤120 words per prose section): "
                + ", ".join(required_sections) + ". The Investigation section MUST contain a "
                "fenced ```csv block. NEVER drop, rename, or collapse a section. ")
    # DESIGNER framing (not EDITOR): the meta-LLM is asked to write a NEW prompt from scratch,
    # using the current best only as *inspiration*. This mirrors the framing the original
    # Hand-Version was authored under — one-shot design against a target structure, not
    # incremental fix. The current prompt is passed at the end labelled explicitly as REFERENCE.
    focus_briefs = {
        "recall": ("MAXIMISE cells extracted. Add or sharpen per-field examples, be more "
                   "aggressive about filling every field the paper states, list per-field "
                   "hints for fields where recall has been low. Do NOT tell the extractor to "
                   "omit uncertain values. Do NOT reduce the aggressiveness of any existing "
                   "extraction instruction."),
        "conciseness": ("Tighten ONLY the prose sections (Abstract Summary, Advances, "
                        "Additional Remarks, Content in Detail, Catalyst, Photosensitizer) "
                        "toward ≤120 words each. DO NOT touch the CSV field list, the per-"
                        "field extraction rules, or the row-coverage instructions. Every "
                        "extraction rule from the reference stays. Recall must not drop."),
        "units": ("Add or sharpen canonical-unit CONVERSION rules for concentration (mM), "
                  "time (h), and turnover frequency (h⁻¹). Values in the CSV are always in "
                  "the field's canonical unit — the extractor must CONVERT (e.g. µM /1000 → mM) "
                  "BEFORE writing the cell. This is a conversion, NOT a filter — never "
                  "instruct the extractor to skip a cell because the paper's unit is ambiguous."),
        "polish": ("Free choice — pick whichever of {recall, conciseness, units} you judge "
                   "has the highest remaining leverage. F1/recall stays the top priority."),
    }
    focus_brief = focus_briefs.get(focus, focus_briefs["polish"])
    sys_part = ("You are a chemistry-domain prompt DESIGNER. Your job is to WRITE a NEW extraction "
                "prompt for photocatalytic-experiment PDFs, not to edit an existing one. Output "
                "format: " + struct + ". " + sect
                + "\n\nCRITICAL PRIORITY ORDER (never violate): "
                "(1) F1 / recall — the new prompt must NOT reduce the number of correctly "
                "extracted cells vs the reference. If any design choice below would reduce cells, "
                "do NOT make it. "
                "(2) Layout — all required sections present, Investigation contains a fenced "
                "```csv block with the exact header. "
                "(3) Conciseness + unit-correctness — secondary polish only.\n\n"
                f"THIS ITERATION'S FOCUS: {focus.upper()} — {focus_brief}\n\n"
                "You will receive: (a) the target Gold-Standard output example (this is the exact "
                "structure to hit), (b) accumulated error patterns from ALL previous prompts in "
                "this run (persistent weak fields, systematic unit confusions, chronic bloat), "
                "(c) the current best prompt as REFERENCE (you may inherit any wording from it, "
                "but you are also free to discard whatever you think won't serve the target).\n\n"
                "Respond with the FULL new prompt text only — no commentary.")
    ground = ""
    if agg.get("groundedness") is not None:
        ground = (f"Groundedness={agg['groundedness']:.3f} Hallucination={agg['hallucination']:.3f} "
                  f"(every value MUST be supported by the paper; never invent or guess values).\n")
    layout_line = ""
    if agg.get("layoutScore") is not None:
        layout_line = (f"Layout score={agg['layoutScore']:.3f} (1.0 = all required sections present).\n")
    conc_line = ""
    if agg.get("conciseness") is not None:
        over = agg.get("over_sections") or []
        over_str = ", ".join(f"{n} ({w} words)" for n, w in over[:3]) if over else "none"
        conc_line = (f"Conciseness={agg['conciseness']:.3f}. Over-length prose sections in the "
                     f"latest run: {over_str}.\n")
    unit_line = ""
    if agg.get("unitCorrectness") is not None:
        unit_line = (f"Unit-correctness={agg['unitCorrectness']:.3f}.\n")
    gold_block = ""
    if gold_example:
        gold_block = ("\n[TARGET GOLD-STANDARD OUTPUT — the new prompt must make the extractor "
                      "produce output in exactly this structure]\n"
                      + gold_example
                      + "\n[END TARGET]\n")
    # cross-iteration memory: accumulated patterns across the whole run
    memory_block = ""
    if memory and memory["iterations_seen"] > 0:
        pw = memory.get("persistent_weak_fields") or []
        pu = memory.get("persistent_unit_error_fields") or []
        pb = memory.get("persistent_over_sections") or []
        parts = [f"[ACCUMULATED PATTERNS across {memory['iterations_seen']} prior iterations]"]
        if pw:
            parts.append("Persistently weak fields (mean recall < 0.30, list oldest→newest):")
            for f, r, n in pw:
                parts.append(f"  - {f}: mean recall {r:.2f} across {n} iters")
        if pu:
            parts.append("Fields with recurring UNIT-FAMILY confusion (µM↔mM, h↔s, etc.):")
            for f, u in pu:
                parts.append(f"  - {f}: {u} unit-error mismatches accumulated")
        if pb:
            parts.append("Sections that consistently went over the 120-word budget:")
            for name, words in pb:
                parts.append(f"  - {name}: seen up to {words} words")
        memory_block = "\n".join(parts) + "\n"
    task = (f"[LAST-ITER METRIC] F1={agg['f1']:.4f} P={agg['precision']:.4f} R={agg['recall']:.4f} "
            f"composite={agg.get('composite', agg['f1']):.4f}\n"
            f"{layout_line}{conc_line}{unit_line}{ground}\n"
            f"{memory_block}\n"
            f"[WEAK FIELDS - LAST ITER]\n{weak}\n\n"
            f"[MISMATCH EXAMPLES - LAST ITER]\n- {ex}\n"
            f"{gold_block}\n"
            f"[CURRENT BEST PROMPT - REFERENCE ONLY, feel free to keep or discard any part]\n"
            f"{current}")
    payload = {"model": model, "input": [
        {"role": "developer", "content": [{"type": "input_text", "text": sys_part}]},
        {"role": "user", "content": [{"type": "input_text", "text": task}]}]}
    out = output_text(post(payload, key)).strip()
    m = re.match(r"^```[a-zA-Z]*\s*\n(.*)\n```$", out, re.S)
    return (m.group(1).strip() if m else out) or current

# ---------- structured operator choice + regression guard ----------
# Restrict which operators are surfaced to the meta-LLM based on this iteration's focus.
# Keeps the model from proposing conciseness edits during a recall-focus iteration.
FOCUS_OP_MENU = {
    "recall":      ["add_field_example", "strengthen_recall", "insert_row_coverage_hint", "no_op"],
    "units":       ["add_unit_rule", "add_field_example", "no_op"],
    "conciseness": ["tighten_section", "dedupe_trigrams", "no_op"],
    "polish":      ["add_field_example", "strengthen_recall", "add_unit_rule",
                    "tighten_section", "dedupe_trigrams", "insert_row_coverage_hint", "no_op"],
}

def _parse_operator_choice(text):
    """Extract a {operator, args, rationale} dict from an LLM response, robust against
    markdown code fences and stray commentary. Returns None on failure."""
    if not text:
        return None
    # strip a ```json ... ``` fence if present
    m = re.search(r"```(?:json)?\s*(\{.*?\})\s*```", text, re.S)
    body = m.group(1) if m else text
    # otherwise find the first {...} span
    if not m:
        m2 = re.search(r"\{.*\}", body, re.S)
        if not m2:
            return None
        body = m2.group(0)
    try:
        obj = json.loads(body)
    except json.JSONDecodeError:
        return None
    if isinstance(obj, dict) and "operator" in obj:
        return obj
    return None

def choose_operator(prompt, agg, memory, focus, fields, model, key,
                    designer_model=None, designer_key=None):
    """Ask the meta-LLM to pick ONE deterministic edit-operator (from the focus menu) to
    apply to the current prompt. Returns {operator, args, rationale}. Falls back to no_op
    on any failure (API error, parse error, unknown operator) so the loop never crashes."""
    menu = FOCUS_OP_MENU.get(focus, FOCUS_OP_MENU["polish"])
    metric_line = (
        f"F1={agg.get('f1', 0):.3f} "
        f"Layout={(agg.get('layoutScore') or 1.0):.2f} "
        f"Concise={(agg.get('conciseness') or 1.0):.2f} "
        f"UnitOK={(agg.get('unitCorrectness') or 1.0):.2f} "
        f"Composite={agg.get('composite', 0):.3f}"
    )
    # weak fields (top 6) with unit-error split so the model can pick the right operator
    weak_lines = []
    for entry in agg.get("weak", [])[:6]:
        k, r, c, g = entry[0], entry[1], entry[2], entry[3]
        u = entry[4] if len(entry) > 4 else 0
        m_ = entry[5] if len(entry) > 5 else max(0, g - c)
        weak_lines.append(f"  {k}: recall {r:.2f} ({c}/{g}), unit_errors {u}/{m_}")
    weak_str = "\n".join(weak_lines) or "  (none)"
    # persistent bloat (cross-iteration memory)
    over_lines = []
    if memory:
        for name, words in memory.get("persistent_over_sections", [])[:5]:
            over_lines.append(f"  {name}: seen up to {words} words (target ≤{MAX_SECTION_WORDS})")
    over_str = "\n".join(over_lines) or "  (none)"
    # persistent unit confusions
    unit_err_lines = []
    if memory:
        for f, u in memory.get("persistent_unit_error_fields", [])[:5]:
            unit_err_lines.append(f"  {f}: {u} accumulated unit-family confusions")
    unit_err_str = "\n".join(unit_err_lines) or "  (none)"
    # available operators with concise arg signatures the model can read at a glance
    op_signatures = {
        "add_field_example": '{"field": "<name>", "example": "<canonical example value>"}',
        "strengthen_recall": '{"field": "<name>"}',
        "add_unit_rule": '{"family": "concentration|time|frequency", "source": "<unit>", "target": "<unit>", "factor": <float>}',
        "tighten_section": '{"section": "<section title>", "max_words": <int>}',
        "dedupe_trigrams": '{}',
        "insert_row_coverage_hint": '{"pattern": "<short description of desired row diversity>"}',
        "no_op": '{}',
    }
    menu_desc = "\n".join(f"  - {op}({op_signatures[op]})" for op in menu)
    sys_part = (
        "You are a chemistry-domain prompt optimizer. Given the current extraction prompt "
        "and its measured weaknesses, select ONE deterministic edit-operator to apply. Each "
        "operator is a small, targeted modification, not a rewrite. Prefer no_op when no "
        "operator would clearly help — a wasted operator is worse than none. "
        "You MUST respond with valid JSON only, no markdown, no commentary. Schema: "
        '{"operator": "<name>", "args": {...}, "rationale": "<one sentence>"}.'
    )
    task = (
        f"[FOCUS] {focus}\n"
        f"[METRICS] {metric_line}\n\n"
        f"[WEAK FIELDS - latest iteration]\n{weak_str}\n\n"
        f"[SECTIONS THAT EXCEED WORD BUDGET - across the whole run]\n{over_str}\n\n"
        f"[PERSISTENT UNIT-FAMILY CONFUSIONS]\n{unit_err_str}\n\n"
        f"[CANONICAL FIELDS]\n{', '.join(fields[:20])}\n\n"
        f"[AVAILABLE OPERATORS - choose ONE]\n{menu_desc}\n\n"
        "Respond with JSON only."
    )
    dm = designer_model or model
    dk = designer_key or key
    # Route to Anthropic API when a claude-* designer model is requested — this breaks
    # the o3-critiques-o3 self-similarity that kept collapsing prompt quality in prior runs.
    is_anthropic = isinstance(dm, str) and dm.startswith("claude")
    if is_anthropic:
        payload = {"model": dm, "max_tokens": 2048, "system": sys_part,
                   "messages": [{"role": "user", "content": task}]}
        try:
            resp = post_anthropic(payload, dk)
            text = _anthropic_text(resp).strip()
        except Exception as e:
            return {"operator": "no_op", "args": {}, "rationale": f"anthropic_error:{type(e).__name__}"}
    else:
        payload = {"model": dm, "input": [
            {"role": "developer", "content": [{"type": "input_text", "text": sys_part}]},
            {"role": "user", "content": [{"type": "input_text", "text": task}]}]}
        try:
            resp = post(payload, dk)
            text = output_text(resp).strip()
        except Exception as e:
            return {"operator": "no_op", "args": {}, "rationale": f"api_error:{type(e).__name__}"}
    parsed = _parse_operator_choice(text)
    if parsed is None:
        return {"operator": "no_op", "args": {}, "rationale": "parse_error"}
    op_name = parsed.get("operator")
    if op_name not in DIFF_OPERATORS:
        return {"operator": "no_op", "args": {}, "rationale": f"unknown_op:{op_name}"}
    # enforce focus menu — reject cross-focus proposals
    if op_name != "no_op" and op_name not in menu:
        return {"operator": "no_op", "args": {}, "rationale": f"off_focus:{op_name}"}
    parsed.setdefault("args", {})
    parsed.setdefault("rationale", "")
    return parsed

def regression_score(prompt, subset_entries, fields, model, key, fmt="csv", tolerance=0.1):
    """Score a prompt on a fixed subset (first 10 training papers). Returns aggregate F1.
    Extraction errors count as zero-tp gold-cell losses (worst-case = regression)."""
    scores = []
    for doi, pdf, gold in subset_entries:
        try:
            rows, _tok, _raw = extract(pdf, prompt, fields, model, key, "", fmt)
            s = score_pub(gold, rows, tolerance)
            scores.append(s)
        except Exception:
            scores.append({"tp": 0, "gold": 1, "ext": 0, "perField": {}, "examples": [],
                           "gold_rows": 0, "ext_rows": 0})
        time.sleep(0.3)
    if not scores:
        return 0.0
    return aggregate(scores)["f1"]

def regression_check(old_prompt, new_prompt, subset_entries, fields, model, key,
                     fmt="csv", tolerance=0.1, guard_tol=0.05):
    """Noise-cancelled regression guard. Re-measures BOTH old_prompt and new_prompt on the
    same subset in the SAME temporal batch — this way o3's sampling variance (typ ±2-3 pp
    on 10 papers) affects both measurements similarly and cancels out in the delta.

    Returns (accepted, old_f1, new_f1, delta):
      - accepted = True iff (new_f1 - old_f1) >= -guard_tol (i.e., not a real regression)
      - default guard_tol 0.05 (5 pp) is wider than the naive 0.03 to absorb residual noise
    Cost: 2 × |subset| API calls per check (vs 1 × before, but the previous single-pass
    guard was fooled by run-to-run variance and let real regressions through).
    """
    old_f1 = regression_score(old_prompt, subset_entries, fields, model, key, fmt, tolerance)
    new_f1 = regression_score(new_prompt, subset_entries, fields, model, key, fmt, tolerance)
    delta = new_f1 - old_f1
    accepted = delta >= -guard_tol
    return accepted, old_f1, new_f1, delta

def _score_prompt_on_entries(prompt, entries, fields, model, key, fmt, tolerance,
                              required_sections, do_ground=False, hints_fn=None,
                              log_prefix="  ", verbose=True):
    """Full evaluation of one prompt over a list of entries. Returns (agg, diag, tokens,
    layout_scores, conciseness_scores). Used by both the main iteration and held-out
    validation, and reused across K parallel candidates."""
    scores, toks, diag = [], 0, []
    gsup = gchk = 0
    layout_scores, conciseness_scores = [], []
    for doi, pdf, gold in entries:
        try:
            hints = hints_fn(doi) if hints_fn else ""
            rows, t, raw = extract(pdf, prompt, fields, model, key, hints, fmt)
            toks += t
            s = score_pub(gold, rows, tolerance)
            scores.append(s)
            ls = layout_score(raw, required_sections)
            layout_scores.append(ls["score"])
            cs = conciseness_score(raw, required_sections) if required_sections else \
                {"score": 1.0, "verbosity": 0.0, "redundancy": 0.0, "over_sections": []}
            conciseness_scores.append(cs["score"])
            if verbose:
                line = (f"{log_prefix}{doi}: matched {s['tp']}/{s['gold']} gold cells, "
                        f"rows {s['ext_rows']}/{s['gold_rows']}, {t} tokens")
                if required_sections:
                    line += f", layout {ls['present']}/{ls['required']}"
                    if ls["missing"]:
                        line += (" (missing: " + ", ".join(ls["missing"][:3])
                                 + (",…" if len(ls["missing"]) > 3 else "") + ")")
                    line += f", concise {cs['score']:.2f}"
                    if cs["over_sections"]:
                        line += (" (long: " + ", ".join(f"{n}:{w}w" for n, w in cs["over_sections"][:2]) + ")")
                grec = None
                if do_ground:
                    g = ground_check(pdf, rows, model, key)
                    gsup += g["supported"]; gchk += g["checked"]
                    grec = g
                    line += f", grounded {g['supported']}/{g['checked']}"
                print(line)
            else:
                grec = None
                if do_ground:
                    g = ground_check(pdf, rows, model, key)
                    gsup += g["supported"]; gchk += g["checked"]
                    grec = g
            diag.append({"doi": doi, "tp": s["tp"], "gold_cells": s["gold"],
                         "ext_cells": s["ext"], "gold_rows": s["gold_rows"],
                         "ext_rows": s["ext_rows"], "layout": ls, "conciseness": cs,
                         "ground": grec, "examples": s["examples"][:10]})
        except Exception as e:
            if verbose:
                print(f"{log_prefix}{doi}: ERROR {e}")
        time.sleep(1)
    if not scores:
        return None, [], 0, [], []
    agg = aggregate(scores)
    agg["groundedness"] = (gsup / gchk) if (do_ground and gchk) else None
    agg["hallucination"] = (1 - gsup / gchk) if (do_ground and gchk) else None
    agg["layoutScore"] = ((sum(layout_scores) / len(layout_scores))
                         if layout_scores and required_sections else None)
    agg["conciseness"] = ((sum(conciseness_scores) / len(conciseness_scores))
                         if conciseness_scores and required_sections else None)
    # cross-publication over-length aggregation (for the meta-LLM's cross-iter memory)
    over_agg = {}
    for d in diag:
        for name, words in d.get("conciseness", {}).get("over_sections", []):
            if words > over_agg.get(name, 0):
                over_agg[name] = words
    agg["over_sections"] = sorted(over_agg.items(), key=lambda x: -x[1])[:5]
    return agg, diag, toks, layout_scores, conciseness_scores

def _append_csv_contract(prompt, required_sections, fields):
    """Bake the exact CSV column contract and required MediaWiki sections into a prompt so
    it is live-deployable to the wiki. Idempotent: skips appending if the marker is already
    present (prevents double-appending on candidate seed sharing)."""
    if "[OUTPUT FORMAT — INVESTIGATION CSV]" in prompt:
        return prompt
    section_clause = ""
    if required_sections:
        section_clause = (
            "\n\n[OUTPUT SECTIONS - MANDATORY, emit ALL of them in this exact order, each as a "
            "wiki heading (== Name ==) followed by AT LEAST 30 words of substantive paper-specific "
            "content. Missing sections OR placeholder text like '<compact narrative>' or "
            "'(...)' below a heading count as MISSING and INVALIDATE the output. Every heading "
            "MUST have real chemistry content underneath it]\n"
            + "\n".join(f"== {n} ==" for n in required_sections)
            + "\nThe Investigation section must contain ONE fenced ```csv block with the header "
            "below as its first row."
        )
    return prompt + (section_clause +
                     "\n\n[OUTPUT FORMAT — INVESTIGATION CSV] Output the experiments as ONE fenced code "
                     "block that starts with ```csv and ends with ```. The header row MUST be EXACTLY "
                     "these columns, in this order:\n"
                     + " , ".join(fields) + "\nOne row per distinct experiment; one value per cell; "
                     "leave a cell empty only if the paper does not state it.")

# ---------- main loop ----------
def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--topic", required=True)
    ap.add_argument("--prompt-file", required=False, default=None,
                    help="seed prompt; if omitted, the model writes the initial prompt itself (cold start)")
    ap.add_argument("--iterations", type=int, default=5)
    ap.add_argument("--limit", type=int, default=0)
    ap.add_argument("--eval-limit", type=int, default=0,
                    help="held-out validation: after optimization, run the winner once on the NEXT "
                         "N papers (immediately after the training set) and report unseen-set "
                         "F1/Layout/Composite. Defends against in-sample overfit (Goodhart).")
    ap.add_argument("--model", default="o3")
    ap.add_argument("--format", choices=["json", "csv"], default="json",
                    help="json = structured output; csv = produce a ```csv table (matches the live wiki import)")
    ap.add_argument("--tolerance", type=float, default=0.1)
    ap.add_argument("--export-prompt", action="store_true")
    ap.add_argument("--ground", action="store_true", help="verify each extracted value against the PDF (groundedness / no-hallucination)")
    ap.add_argument("--field-hints", action="store_true", help="give the model example value formats per field, sampled from OTHER gold papers (leakage-free)")
    ap.add_argument("--commit", action="store_true", help="git-commit the results after each iteration (archive the progression)")
    ap.add_argument("--candidates", type=int, default=1,
                    help="beam width: run K parallel candidate prompts, best composite wins. "
                         "K=1 preserves legacy single-line behavior (with diff operators). "
                         "K=5 recommended for full-quality optimization.")
    ap.add_argument("--regression-guard-tol", type=float, default=0.05,
                    help="reject a diff operator if (new_subset_F1 − re-measured_baseline_F1) "
                         "< −tol (default 0.05 = 5 pp). Second defense layer beyond hill-climb. "
                         "0.05 is wider than the naive 0.03 to absorb o3 sampling noise; the "
                         "noise-cancelling comparison (re-measure both in the same batch) is what "
                         "makes this trustworthy.")
    ap.add_argument("--designer-model", default=None,
                    help="model used for choose_operator (default = --model). Set to "
                         "claude-* to break o3/o3 self-similarity via cross-model.")
    ap.add_argument("--designer-key-file", default=None,
                    help="path to API key for --designer-model (default: same as extractor). "
                         "For claude-* models, default is ~/.config/chemwiki/anthropic.key.")
    a = ap.parse_args()
    key = api_key()
    # Designer key: separate file for cross-model Anthropic calls. Falls back to OpenAI key.
    designer_key = key
    if a.designer_model and a.designer_model.startswith("claude"):
        akp = a.designer_key_file or os.path.expanduser("~/.config/chemwiki/anthropic.key")
        if os.path.isfile(akp):
            designer_key = open(akp).read().strip()
        else:
            print(f"WARN: Anthropic key not at {akp} — falling back to OpenAI extractor as designer")
            a.designer_model = None

    gold_dir = os.path.join(EVAL, a.topic, "gold")
    all_entries = []
    for gj in sorted(glob.glob(os.path.join(gold_dir, "*.json"))):
        d = json.load(open(gj))
        pdf = os.path.join(EVAL, a.topic, d.get("pdf", ""))
        if os.path.isfile(pdf) and d.get("experiments"):
            all_entries.append((d["doi"], pdf, d["experiments"]))
    # split: training set (first --limit papers) the optimizer sees during iteration,
    # held-out set (next --eval-limit papers) only the WINNER prompt is evaluated against
    if a.limit:
        entries = all_entries[:a.limit]
        holdout = all_entries[a.limit:a.limit + a.eval_limit] if a.eval_limit else []
    else:
        entries = all_entries
        holdout = []
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
    cols = ["iteration", "f1", "f1_best", "precision", "recall", "groundedness", "hallucination",
            "layoutScore", "conciseness", "unitCorrectness", "composite", "composite_best", "tokensPerPub"]
    rows_csv = [",".join(cols)]

    # required sections for layout/structure scoring (per topic, overridable in profile.json)
    required_sections = list(REQUIRED_SECTIONS_BY_TOPIC.get(a.topic, []))
    profile_path = os.path.join(EVAL, a.topic, "profile.json")
    if os.path.isfile(profile_path):
        try:
            prof = json.load(open(profile_path))
            if isinstance(prof.get("required_sections"), list):
                required_sections = prof["required_sections"]
        except Exception:
            pass
    if a.format != "csv":
        # JSON output has no notion of wiki sections — disable layout check there
        required_sections = []
    if required_sections:
        print(f"layout check on, {len(required_sections)} required sections: "
              + ", ".join(required_sections))

    # Optional gold-standard example: if eval/<topic>/gold_example.wiki exists, the meta-LLM
    # sees a fully curated reference page so it knows the EXACT desired output shape — same
    # context a human prompt-engineer would have had.
    gold_example_path = os.path.join(EVAL, a.topic, "gold_example.wiki")
    gold_example = open(gold_example_path).read().strip() if os.path.isfile(gold_example_path) else None
    if gold_example:
        print(f"gold-standard example loaded ({len(gold_example)} chars) — used to guide the meta-LLM")

    if a.prompt_file:
        prompt = open(a.prompt_file).read().strip()
    else:
        print("No --prompt-file given -> model writes the initial prompt itself (cold start)...")
        prompt = generate_initial_prompt(fields, a.format, a.model, key, required_sections, gold_example)
        os.makedirs(results_dir, exist_ok=True)
        open(os.path.join(results_dir, "seed_generated.txt"), "w").write(prompt)
    # bake CSV format contract into the base prompt (for K2..K5 that reuse it)
    if a.format == "csv":
        prompt = _append_csv_contract(prompt, required_sections, fields)
    # ---- K parallel candidates (Beam) ----
    # Each candidate is an independent prompt lineage with its own hill-climb,
    # focus schedule, and diff-operator sequence. Winner = argmax composite across all.
    # K=1 preserves single-line behavior (now with diff operators, still bounded by
    # hill-climb + regression guard). K=5 = full-quality search.
    K = max(1, int(a.candidates))
    regression_subset = entries[:min(10, len(entries))]
    print(f"regression-guard on: first {len(regression_subset)} papers, tolerance ±{a.regression_guard_tol:g}")
    print(f"K = {K} parallel candidate(s), designer-model = {a.designer_model or a.model}")

    # Per-candidate focus schedules — each candidate has a distinct optimization strategy
    # so the beam explores multiple angles rather than one line.
    CANDIDATE_SCHEDULES = {
        "K1": ["recall", "recall", "units", "conciseness", "polish", "polish"],  # rotate
        "K2": ["recall"] * 12,     # aggressive recall
        "K3": ["units"] * 12,      # unit-focused
        "K4": ["conciseness", "conciseness", "dedupe", "conciseness", "polish", "polish"],
        "K5": ["recall", "units", "conciseness", "polish", "recall", "polish"],  # rotate variant
    }
    def focus_for(cand_id, it):
        sched = CANDIDATE_SCHEDULES.get(cand_id, ["polish"] * 12)
        return sched[(it - 1) % len(sched)] if sched else "polish"

    # Initialize candidates. If a --prompt-file was passed we seed K2..K5 with it; K1 is
    # always a fresh cold-start (reproduces the original Hand-Version-session as a floor).
    print("\ninitializing candidates...")
    candidates = []
    for i in range(1, K + 1):
        cid = f"K{i}"
        if i == 1 or not a.prompt_file:
            # Cold-start: model designs from scratch. K1 always cold-start for beam diversity.
            print(f"  {cid}: cold-start (designer writes initial prompt)...")
            cand_prompt = generate_initial_prompt(fields, a.format, a.model, key,
                                                   required_sections, gold_example)
            if a.format == "csv":
                cand_prompt = _append_csv_contract(cand_prompt, required_sections, fields)
        else:
            # Hand-Version (or user-provided seed)
            print(f"  {cid}: seeded from {os.path.basename(a.prompt_file)}")
            cand_prompt = prompt  # already csv-contract-appended above
        candidates.append({
            "id": cid,
            "prompt": cand_prompt,
            "best": {"prompt": cand_prompt, "composite": -1.0, "agg": None, "iter": 0, "f1": -1.0},
            "hist": [],
            "reg_baseline": None,   # regression-guard F1 on subset; refreshed lazily
        })

    # Long-format CSV with candidate dimension. Legacy single-line CSV kept as a projection.
    csv_cols = ["candidate", "iteration", "focus", "f1", "f1_best", "precision", "recall",
                "layoutScore", "conciseness", "unitCorrectness",
                "composite", "composite_best", "tokensPerPub",
                "operator", "op_accepted", "op_rationale"]
    rows_csv = [",".join(csv_cols)]

    for it in range(1, a.iterations + 1):
        print(f"\n=== iteration {it}/{a.iterations} ===")
        for cand in candidates:
            cid = cand["id"]
            focus = focus_for(cid, it)
            print(f"\n-- candidate {cid} (iter {it}, focus={focus}) --")
            # archive per-candidate prompt (paper trail)
            cand_prompts_dir = os.path.join(results_dir, "candidates", cid, "prompts")
            os.makedirs(cand_prompts_dir, exist_ok=True)
            open(os.path.join(cand_prompts_dir, f"iter_{it}.txt"), "w").write(cand["prompt"])

            hints_fn = (lambda doi: field_hints(fd, doi, fields)) if a.field_hints else None
            agg, diag, toks, layout_scores, conciseness_scores = _score_prompt_on_entries(
                cand["prompt"], entries, fields, a.model, key, a.format, a.tolerance,
                required_sections, do_ground=a.ground, hints_fn=hints_fn, log_prefix="    ",
                verbose=True)
            if agg is None:
                print(f"    {cid}: no publication could be scored — skipping candidate this iter")
                continue
            avg_tok = toks // max(1, len(entries))
            composite = composite_objective(agg["f1"], agg["layoutScore"], agg["conciseness"])
            agg["composite"] = composite

            # Hill-climb per candidate
            was_best = composite > cand["best"]["composite"]
            if was_best:
                cand["best"] = {"prompt": cand["prompt"], "composite": composite,
                                "agg": agg, "iter": it, "f1": agg["f1"]}
                # invalidate regression baseline — new best warrants re-computation on next diff
                cand["reg_baseline"] = None

            # Extract subset F1 from diag (regression baseline for the guard)
            if cand["reg_baseline"] is None:
                subset_dois = {t[0] for t in regression_subset}
                subset_d = [d for d in diag if d["doi"] in subset_dois]
                if subset_d:
                    stp = sum(d["tp"] for d in subset_d)
                    sgold = sum(d["gold_cells"] for d in subset_d)
                    sext = sum(d["ext_cells"] for d in subset_d)
                    sr = stp / sgold if sgold else 0
                    sp = stp / sext if sext else 0
                    cand["reg_baseline"] = (2 * sp * sr / (sp + sr)) if (sp + sr) else 0.0

            # Log aggregate
            marker = "★" if was_best else " "
            print(f"    {marker} AGG F1={agg['f1']:.4f} P={agg['precision']:.4f} R={agg['recall']:.4f} "
                  f"layout={agg['layoutScore'] or 1.0:.3f} conc={agg['conciseness'] or 1.0:.3f} "
                  f"unit_ok={agg.get('unitCorrectness') or 1.0:.3f} "
                  f"composite={composite:.4f} tok/pub={avg_tok} | "
                  f"best F1={cand['best']['f1']:.4f} composite={cand['best']['composite']:.4f}")

            # Record hist for cross-iter memory
            cand["hist"].append({
                "iteration": it,
                "f1": agg["f1"], "precision": agg["precision"], "recall": agg["recall"],
                "layoutScore": agg["layoutScore"], "conciseness": agg["conciseness"],
                "unitCorrectness": agg.get("unitCorrectness"),
                "composite": composite, "composite_best": cand["best"]["composite"],
                "f1_best": cand["best"]["f1"], "tokensPerPub": avg_tok,
                "weak_fields_raw": [{"field": k, "recall": r, "correct": c, "total": g,
                                     "unit_errors": u, "misses": m}
                                    for k, r, c, g, u, m in agg["weak"]],
                "over_sections": agg.get("over_sections") or [],
            })

            # Diff-operator proposal + regression guard (only if not last iter)
            op_name, op_accepted, op_rationale = "no_op", False, "last_iter"
            if it < a.iterations:
                memory = build_memory(cand["hist"])
                op_choice = choose_operator(
                    cand["best"]["prompt"], cand["best"]["agg"], memory, focus, fields,
                    a.model, key,
                    designer_model=a.designer_model, designer_key=designer_key)
                op_name = op_choice["operator"]
                op_rationale = op_choice.get("rationale", "")[:80]
                if op_name == "no_op":
                    print(f"    diff: no_op ({op_rationale})")
                    op_accepted = False
                else:
                    proposed = apply_diff(cand["best"]["prompt"], op_choice)
                    if proposed == cand["best"]["prompt"]:
                        print(f"    diff: {op_name} — no textual change (idempotent skip)")
                        op_accepted = False
                    else:
                        # Noise-cancelled regression guard: re-measure BOTH baseline and
                        # proposed prompt on the same subset in the same batch. The prior
                        # single-pass guard was fooled by o3 sampling variance (~2-3 pp) —
                        # comparing a same-batch pair cancels that noise to first order.
                        accepted, reg_old, reg_new, delta = regression_check(
                            cand["best"]["prompt"], proposed, regression_subset, fields,
                            a.model, key, a.format, a.tolerance, a.regression_guard_tol)
                        if not accepted:
                            print(f"    diff: {op_name} REJECTED "
                                  f"(new {reg_new:.3f} vs re-measured baseline {reg_old:.3f} "
                                  f"= Δ{delta:+.3f}, guard −{a.regression_guard_tol:g})")
                            op_accepted = False
                        else:
                            cand["prompt"] = proposed
                            # cache new re-measured baseline for the next iter (no fresh
                            # measurement needed at start of next iter — this saves 10 calls)
                            cand["reg_baseline"] = reg_new
                            print(f"    diff: {op_name} ACCEPTED "
                                  f"(new {reg_new:.3f} vs re-measured baseline {reg_old:.3f} "
                                  f"= Δ{delta:+.3f}) | {op_rationale}")
                            op_accepted = True

            # CSV row
            rows_csv.append(
                f"{cid},{it},{focus},"
                f"{agg['f1']:.4f},{cand['best']['f1']:.4f},"
                f"{agg['precision']:.4f},{agg['recall']:.4f},"
                f"{(agg['layoutScore'] or 1.0):.4f},"
                f"{(agg['conciseness'] or 1.0):.4f},"
                f"{(agg.get('unitCorrectness') or 1.0):.4f},"
                f"{composite:.4f},{cand['best']['composite']:.4f},{avg_tok},"
                f"{op_name},{int(op_accepted)},\"{op_rationale.replace(chr(34), chr(39))}\""
            )

        # Write CSV after each full iteration across all candidates
        open(csv_path, "w").write("\n".join(rows_csv) + "\n")
        if a.commit:
            best_across = max(candidates, key=lambda c: c["best"]["composite"])
            git_commit([results_dir],
                       f"eval {a.topic} iter {it}: best-of-K={K} F1={best_across['best']['f1']:.4f} "
                       f"composite={best_across['best']['composite']:.4f}")

    # ---- Winner = argmax composite across all candidates ----
    winner_cand = max(candidates, key=lambda c: c["best"]["composite"])
    best = winner_cand["best"]
    hist = winner_cand["hist"]
    print(f"\nBest-of-K={K} winner: candidate {winner_cand['id']}, "
          f"F1={best['f1']:.4f}, composite={best['composite']:.4f} (iter {best['iter']})")
    for c in candidates:
        print(f"  {c['id']}: best F1={c['best']['f1']:.4f} composite={c['best']['composite']:.4f}")
    open(os.path.join(results_dir, "best_prompt.txt"), "w").write(best["prompt"])
    # Write winner marker for downstream tooling
    with open(os.path.join(results_dir, "winner.json"), "w") as f:
        json.dump({"winner_candidate": winner_cand["id"], "composite": best["composite"],
                   "f1": best["f1"], "iter": best["iter"],
                   "candidates_summary": [{"id": c["id"], "f1": c["best"]["f1"],
                                           "composite": c["best"]["composite"]}
                                          for c in candidates]}, f, indent=2)
    write_paper_artifacts(results_dir, hist, best, a.topic)
    print(f"Paper artefacts: {results_dir}/trend.pdf, summary.md, metrics.tex, metrics.csv")

    # ---- held-out validation (Goodhart defense): run the WINNER prompt on unseen papers
    if holdout:
        print(f"\n=== held-out validation on {len(holdout)} unseen papers ===")
        h_scores, h_toks, h_layout, h_conc = [], 0, [], []
        for doi, pdf, gold in holdout:
            try:
                rows, t, raw = extract(pdf, best["prompt"], fields, a.model, key, "", a.format)
                h_toks += t
                s = score_pub(gold, rows, a.tolerance)
                h_scores.append(s)
                ls = layout_score(raw, required_sections)
                h_layout.append(ls["score"])
                cs = conciseness_score(raw, required_sections) if required_sections else \
                    {"score": 1.0, "over_sections": []}
                h_conc.append(cs["score"])
                line = f"  {doi}: matched {s['tp']}/{s['gold']} gold cells, rows {s['ext_rows']}/{s['gold_rows']}, {t} tokens"
                if required_sections:
                    line += f", layout {ls['present']}/{ls['required']}, concise {cs['score']:.2f}"
                print(line)
            except Exception as e:
                print(f"  {doi}: ERROR {e}")
            time.sleep(1)
        if h_scores:
            agg_h = aggregate(h_scores)
            layout_h = (sum(h_layout) / len(h_layout)) if h_layout and required_sections else None
            conc_h = (sum(h_conc) / len(h_conc)) if h_conc and required_sections else None
            comp_h = composite_objective(agg_h["f1"], layout_h, conc_h)
            unit_h = agg_h.get("unitCorrectness")
            lstr_h = f" layout={layout_h:.3f}" if layout_h is not None else ""
            cstr_h = f" conc={conc_h:.3f}" if conc_h is not None else ""
            ustr_h = f" unit_ok={unit_h:.3f}" if unit_h is not None else ""
            print(f"  HOLDOUT F1={agg_h['f1']:.4f} P={agg_h['precision']:.4f} R={agg_h['recall']:.4f}"
                  f"{lstr_h}{cstr_h}{ustr_h} composite={comp_h:.4f} tok/pub={h_toks // len(h_scores)} "
                  f"(training best F1={best['f1']:.4f} composite={best.get('composite', best['f1']):.4f})")
            with open(os.path.join(results_dir, "holdout.csv"), "w") as f:
                f.write("n_papers,f1,precision,recall,layoutScore,conciseness,unitCorrectness,"
                        "composite,tokensPerPub,training_f1,training_composite\n")
                f.write(f"{len(h_scores)},{agg_h['f1']:.4f},{agg_h['precision']:.4f},"
                        f"{agg_h['recall']:.4f},"
                        f"{(layout_h if layout_h is not None else 1.0):.4f},"
                        f"{(conc_h if conc_h is not None else 1.0):.4f},"
                        f"{(unit_h if unit_h is not None else 1.0):.4f},"
                        f"{comp_h:.4f},"
                        f"{h_toks // len(h_scores)},{best['f1']:.4f},"
                        f"{best.get('composite', best['f1']):.4f}\n")

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
