#!/usr/bin/env python3
"""Unit tests for optimize_local.py new metrics (conciseness + unit-error)."""
import os, sys
HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)

from optimize_local import (
    conciseness_score, is_unit_error, layout_score,
    MAX_SECTION_WORDS, MIN_SECTION_WORDS,
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
