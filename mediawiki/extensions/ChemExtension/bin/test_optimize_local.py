#!/usr/bin/env python3
"""Unit tests for optimize_local.py new metrics (conciseness + unit-error)."""
import os, sys
HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)

from optimize_local import (
    conciseness_score, is_unit_error, layout_score,
    MAX_SECTION_WORDS, MIN_SECTION_WORDS,
    DIFF_OPERATORS, apply_diff,
    op_add_field_example, op_strengthen_recall, op_add_unit_rule,
    op_tighten_section, op_dedupe_trigrams, op_insert_row_coverage_hint, op_no_op,
)

SECTIONS = ["Abstract Summary", "Advances and Special Progress", "Additional Remarks",
            "Content of the Published Article in Detail", "Catalyst", "Photosensitizer",
            "Investigation"]

# distinct filler pools per section so trigrams don't accidentally overlap in the "perfect" test.
_FILLER = {
    "Abstract Summary": ("study explores homogeneous molecular systems capable of visible-light "
                         "driven proton reduction under mild aqueous conditions using earth "
                         "abundant metals paired with cheap organic donors").split(),
    "Advances and Special Progress": ("primary novelty resides in ligand redox non-innocence that "
                                      "raises selectivity via ultrafast intramolecular charge shuttle "
                                      "surpassing benchmark bipyridyl analogues at low loading").split(),
    "Additional Remarks": ("supporting information includes crystallographic refinement details "
                           "isotope labelling experiments quantum yield measurements electrode "
                           "cyclic voltammetry data cross validation temperature dependence").split(),
    "Content of the Published Article in Detail": ("article describes synthesis characterisation "
                                                   "mechanistic exploration spectroelectrochemical "
                                                   "analysis kinetic modelling density functional "
                                                   "theory calculations correlation between structure "
                                                   "electrochemistry").split(),
    "Catalyst": ("iron centres bear pyrazolyl phenanthroline scaffolds tuned by substituent "
                 "electronics halide trifluoromethyl variants yield distinct redox potentials "
                 "showing largest activity in electron poor complex").split(),
    "Photosensitizer": ("iridium tris phenylpyridine chosen for long lived triplet state pairing "
                        "with sacrificial reagent providing strongly reducing driving force under "
                        "monochromatic excitation four hundred nanometre light emitting diode").split(),
}

def _para(section, n_words=40):
    """Return an n_words paragraph using the section's distinct filler pool (repeated as needed)."""
    pool = _FILLER.get(section, ["placeholder"] * 30)
    out = []
    while len(out) < n_words:
        out.extend(pool)
    return " ".join(out[:n_words])

def _short_para(section, n_words=40):
    return _para(section, n_words)

def _long_para(section="Abstract Summary", n_words=240):
    return _para(section, n_words)

def _investigation_block():
    return ("```csv\n"
            "catalyst,cat conc,PS,PS conc,e-D\n"
            "Fe-1,0.05,Ir(ppy)3,0.5,BIH\n"
            "Fe-2,0.05,Ir(ppy)3,0.5,BIH\n"
            "```")

def _make_page(prose_by_section):
    """Compose a wiki page from a dict of {section_name: text}."""
    parts = []
    for name in SECTIONS:
        parts.append(f"== {name} ==")
        if name.lower() == "investigation":
            parts.append(_investigation_block())
        else:
            parts.append(prose_by_section.get(name, _short_para(name, 40)))
    return "\n\n".join(parts)

# ---------- conciseness_score ----------
def test_conciseness_perfect():
    """All sections concise (≈40 words each), distinct content per section → score close to 1.0."""
    text = _make_page({s: _short_para(s, 40) for s in SECTIONS if s != "Investigation"})
    r = conciseness_score(text, SECTIONS)
    assert r["verbosity"] == 0.0, f"verbosity should be 0 for short sections, got {r['verbosity']}"
    assert r["score"] > 0.85, f"expected score > 0.85, got {r['score']} (redundancy={r['redundancy']:.3f})"
    print(f"  ok perfect: score={r['score']:.3f} verbosity={r['verbosity']:.3f} redundancy={r['redundancy']:.3f}")

def test_conciseness_verbose():
    """One 240-word section triggers verbosity penalty."""
    prose = {s: _short_para(s, 40) for s in SECTIONS if s != "Investigation"}
    prose["Abstract Summary"] = _long_para("Abstract Summary", 240)
    text = _make_page(prose)
    r = conciseness_score(text, SECTIONS)
    assert r["verbosity"] > 0.1, f"verbosity should be > 0.1 for 240-word section, got {r['verbosity']}"
    over_names = [n for n, _ in r["over_sections"]]
    assert "Abstract Summary" in over_names, \
        f"Abstract Summary should be in over_sections, got {r['over_sections']}"
    print(f"  ok verbose: score={r['score']:.3f} verbosity={r['verbosity']:.3f} over={r['over_sections']}")

def test_conciseness_redundant():
    """Two identical sections → high pairwise Jaccard → redundancy > 0."""
    identical = ("catalyst photocatalytic hydrogen evolution and CO2 reduction driven by visible "
                 "light irradiation studied using a homogeneous molecular catalyst system paired "
                 "with an iridium photosensitizer and a sacrificial electron donor achieving "
                 "turnovers competitive with earth abundant metal complexes")
    prose = {s: _short_para(s.split()[0].lower(), 30) for s in SECTIONS if s != "Investigation"}
    prose["Catalyst"] = identical
    prose["Photosensitizer"] = identical
    text = _make_page(prose)
    r = conciseness_score(text, SECTIONS)
    assert r["redundancy"] > 0.05, f"redundancy should be > 0.05, got {r['redundancy']}"
    print(f"  ok redundant: score={r['score']:.3f} redundancy={r['redundancy']:.3f}")

def test_conciseness_investigation_exempt():
    """Only Investigation exists → all prose sections empty → score returns default 1.0 (no bodies to measure)."""
    text = "== Investigation ==\n" + _investigation_block()
    r = conciseness_score(text, SECTIONS)
    assert r["score"] == 1.0, f"empty prose should yield score 1.0 (nothing to measure), got {r['score']}"
    print(f"  ok investigation-only: score={r['score']:.3f}")

def test_conciseness_empty():
    """Empty text → no sections found → default 1.0."""
    r = conciseness_score("", SECTIONS)
    assert r["score"] == 1.0
    print(f"  ok empty: score={r['score']:.3f}")

# ---------- is_unit_error ----------
def test_unit_error_1000x():
    ok, factor = is_unit_error(50.0, 0.05)   # µM in ext vs mM in gold
    assert ok, f"50/0.05 = 1000 should be a unit error"
    assert factor == 1000.0, f"expected factor 1000, got {factor}"
    print(f"  ok 1000×: factor={factor}")

def test_unit_error_3600x():
    ok, factor = is_unit_error(3600.0, 1.0)  # s vs h
    assert ok, f"3600/1 should be flagged as h↔s confusion"
    assert abs(factor - 3600.0) < 1.0
    print(f"  ok 3600×: factor={factor}")

def test_unit_error_60x():
    ok, factor = is_unit_error(1.0, 60.0)    # h vs min
    assert ok, f"1/60 should be flagged as h↔min confusion"
    print(f"  ok 60× (inverse): factor={factor}")

def test_unit_error_close_match_not_flagged():
    ok, _ = is_unit_error(50.0, 45.0)        # within tolerance range
    assert not ok, f"50/45 = 1.11 is not a family factor"
    print(f"  ok close-match not flagged")

def test_unit_error_random_factor_not_flagged():
    ok, _ = is_unit_error(100.0, 33.0)       # factor ≈ 3, no family match
    assert not ok, f"100/33 is not a family factor"
    print(f"  ok random-factor not flagged")

def test_unit_error_zero_safe():
    ok, _ = is_unit_error(0.0, 5.0)          # zero → safe False, no divide-by-zero
    assert not ok
    ok, _ = is_unit_error(None, 5.0)
    assert not ok
    print(f"  ok zero/None handling")

# ---------- run all ----------
# ---------- diff operators (Baustein 1) ----------
_STUB_PROMPT = ("You are an extractor. Emit MediaWiki sections + a fenced csv block.\n"
                "== Abstract Summary ==\n(...)\n== Investigation ==\n```csv\nheader\n```")

def test_op_add_field_example_inserts_and_preserves():
    p2 = op_add_field_example(_STUB_PROMPT, "cat conc", "0.05")
    assert "<!-- OP:add_field_example cat conc -->" in p2
    assert "0.05" in p2
    assert _STUB_PROMPT in p2, "original prompt must be preserved verbatim"
    print(f"  ok add_field_example: +{len(p2) - len(_STUB_PROMPT)} chars")

def test_op_add_field_example_idempotent():
    p1 = op_add_field_example(_STUB_PROMPT, "cat conc", "0.05")
    p2 = op_add_field_example(p1, "cat conc", "0.05")
    assert p1 == p2, "second application of same op+args must be no-op"
    print(f"  ok idempotent")

def test_op_add_field_example_different_field_stacks():
    p1 = op_add_field_example(_STUB_PROMPT, "cat conc", "0.05")
    p2 = op_add_field_example(p1, "PS conc", "0.5")
    assert "cat conc" in p2 and "PS conc" in p2
    assert p2.count("<!-- OP:add_field_example") == 2
    print(f"  ok different fields stack correctly")

def test_op_strengthen_recall():
    p2 = op_strengthen_recall(_STUB_PROMPT, "PS conc")
    assert "PS conc" in p2 and "MUST" in p2
    assert "<!-- OP:strengthen_recall PS conc -->" in p2
    print(f"  ok strengthen_recall")

def test_op_add_unit_rule_forward():
    p2 = op_add_unit_rule(_STUB_PROMPT, "concentration", "µM", "mM", 1000)
    assert "µM" in p2 and "mM" in p2 and "1000" in p2
    assert "÷" in p2 or "/1000" in p2 or "1000" in p2
    print(f"  ok add_unit_rule ÷1000")

def test_op_add_unit_rule_inverse():
    p2 = op_add_unit_rule(_STUB_PROMPT, "time", "s", "h", 1.0 / 3600.0)
    assert "s" in p2 and "h" in p2 and "3600" in p2
    assert "×" in p2 or "*" in p2 or "3600" in p2
    print(f"  ok add_unit_rule ×3600")

def test_op_tighten_section():
    p2 = op_tighten_section(_STUB_PROMPT, "Content of the Published Article in Detail", 120)
    assert "Content of the Published Article in Detail" in p2
    assert "120" in p2 and "WORD BUDGET" in p2
    print(f"  ok tighten_section")

def test_op_dedupe_trigrams():
    p2 = op_dedupe_trigrams(_STUB_PROMPT)
    assert "<!-- OP:dedupe_trigrams -->" in p2
    p3 = op_dedupe_trigrams(p2)
    assert p2 == p3, "dedupe_trigrams must be idempotent"
    print(f"  ok dedupe_trigrams (idempotent)")

def test_op_insert_row_coverage_hint():
    p2 = op_insert_row_coverage_hint(_STUB_PROMPT, "distinct λexc / catalyst pairs")
    assert "ROW COVERAGE" in p2
    print(f"  ok row_coverage")

def test_op_no_op():
    assert op_no_op(_STUB_PROMPT) == _STUB_PROMPT
    print(f"  ok no_op")

def test_registry_contains_all_ops():
    expected = {"add_field_example", "strengthen_recall", "add_unit_rule",
                "tighten_section", "dedupe_trigrams", "insert_row_coverage_hint", "no_op"}
    assert set(DIFF_OPERATORS.keys()) == expected
    for name, fn in DIFF_OPERATORS.items():
        assert callable(fn), f"{name} must be callable"
    print(f"  ok registry: {len(DIFF_OPERATORS)} operators")

def test_apply_diff_dispatch():
    p2 = apply_diff(_STUB_PROMPT, {"operator": "add_field_example",
                                    "args": {"field": "λexc", "example": "400"}})
    assert "λexc" in p2 and "400" in p2
    print(f"  ok apply_diff dispatch")

def test_apply_diff_unknown_operator_is_noop():
    p2 = apply_diff(_STUB_PROMPT, {"operator": "nonexistent_operator", "args": {}})
    assert p2 == _STUB_PROMPT, "unknown operator must return prompt unchanged (no crash)"
    print(f"  ok unknown operator handled safely")

def test_apply_diff_bad_args_is_noop():
    p2 = apply_diff(_STUB_PROMPT, {"operator": "add_field_example",
                                    "args": {"wrong_arg_name": "0.05"}})
    assert p2 == _STUB_PROMPT, "arg mismatch must return prompt unchanged"
    print(f"  ok bad args handled safely")

def test_apply_diff_malformed_choice_is_noop():
    assert apply_diff(_STUB_PROMPT, None) == _STUB_PROMPT
    assert apply_diff(_STUB_PROMPT, "not a dict") == _STUB_PROMPT
    assert apply_diff(_STUB_PROMPT, {}) == _STUB_PROMPT  # missing operator key -> no_op
    print(f"  ok malformed choices handled safely")

# ---------- Baustein 2: operator-choice parser ----------
def test_parse_choice_plain_json():
    from optimize_local import _parse_operator_choice
    r = _parse_operator_choice('{"operator": "no_op", "args": {}, "rationale": "ok"}')
    assert r["operator"] == "no_op"
    print("  ok plain JSON parses")

def test_parse_choice_markdown_json_block():
    from optimize_local import _parse_operator_choice
    text = ('Here is my choice:\n```json\n'
            '{"operator": "add_field_example", "args": {"field": "cat conc", "example": "0.05"}, "rationale": "recall"}\n'
            '```\nHope that helps.')
    r = _parse_operator_choice(text)
    assert r["operator"] == "add_field_example"
    assert r["args"]["field"] == "cat conc"
    print("  ok markdown-wrapped JSON parses")

def test_parse_choice_garbage_returns_none():
    from optimize_local import _parse_operator_choice
    assert _parse_operator_choice("I don't want to answer.") is None
    assert _parse_operator_choice("") is None
    print("  ok garbage input returns None")

def test_focus_menu_recall_excludes_conciseness_ops():
    from optimize_local import FOCUS_OP_MENU
    assert "tighten_section" not in FOCUS_OP_MENU["recall"]
    assert "add_field_example" in FOCUS_OP_MENU["recall"]
    print("  ok focus menu enforces focus discipline")

def test_focus_menu_polish_allows_all_ops():
    from optimize_local import FOCUS_OP_MENU
    all_ops = set(FOCUS_OP_MENU["polish"])
    assert "add_field_example" in all_ops and "tighten_section" in all_ops and "add_unit_rule" in all_ops
    print("  ok polish menu is comprehensive")

if __name__ == "__main__":
    tests = [t for t in globals() if t.startswith("test_")]
    failed = 0
    for name in tests:
        try:
            print(f"\n{name}")
            globals()[name]()
        except AssertionError as e:
            failed += 1
            print(f"  FAIL: {e}")
        except Exception as e:
            failed += 1
            print(f"  ERROR: {type(e).__name__}: {e}")
    print(f"\n{'=' * 40}")
    print(f"{'ALL PASS' if not failed else f'{failed} FAILED'}  ({len(tests)} tests)")
    sys.exit(1 if failed else 0)
