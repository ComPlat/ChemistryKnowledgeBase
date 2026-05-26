# Extraction evaluation & self-optimizing prompt loop

This directory holds the **gold set** (manually curated reference extractions) and the
**memory** of the self-optimizing extraction loop, per topic.

> **Data never goes into git.** `eval/.gitignore` excludes all PDFs (copyright), the curated gold
> JSONs and every generated run/report. Only code and the (optimized) prompt pages under
> `wikischema/MediaWiki/Prompt_import_<Topic>.wiki` are version-controlled.

## Two ways to run

- **Local, DB-free (this box): `bin/optimize_local.py`** — pure Python + OpenAI, no MediaWiki/DB.
  Optimizes *prompt + extraction* against the gold set; molecule columns are extracted but not
  scored (needs the wiki molecule DB). Use this where only python is available.
- **In-wiki (the MediaWiki server): `maintenance/optimizeExtractionPrompt.php`** — full pipeline
  incl. molecule resolution, critic, sanity; needs php + DB + SMW.

Both share the gold format, `results/metrics.csv` and the matplotlib trend.

### Local run (python, no key in repo)
```bash
# key lives OUTSIDE the repo:
mkdir -p ~/.config/chemwiki && printf '%s' 'sk-...' > ~/.config/chemwiki/openai.key && chmod 600 ~/.config/chemwiki/openai.key

cd mediawiki/extensions/ChemExtension
# cheap test first (3 papers, 2 rounds):
python3 bin/optimize_local.py --topic Photocatalytic_CO2_conversion \
    --prompt-file ../../../wikischema/MediaWiki/Prompt_import_Photocatalytic_CO2_conversion.wiki \
    --iterations 2 --limit 3 --model o3
# full run, write the winning prompt into the repo:
python3 bin/optimize_local.py --topic Photocatalytic_CO2_conversion \
    --prompt-file ../../../wikischema/MediaWiki/Prompt_import_Photocatalytic_CO2_conversion.wiki \
    --iterations 8 --model o3 --export-prompt
```
Each iteration writes `eval/<Topic>/results/metrics.csv` + `trend.png/pdf`. Stop with Ctrl+C
(prior iterations stay archived). Commit `results/*` + the exported prompt to archive the trend.

## Turnkey (in-wiki / PHP) — once the OpenAI key is set

1. **Config** in `LocalSettings.php`:
   ```php
   $wgOpenAIKey = 'sk-...';                              // required
   // optional:
   $wgOpenAIModel = 'o3';                                // extraction/critic model
   $wgOpenAIEmbeddingModel = 'text-embedding-3-small';   // prose similarity
   $wgCEUnpaywallEmail = 'you@kit.edu';                  // for OA PDF fetching
   ```
2. **Check readiness:** `php maintenance/checkEvalSetup.php` (run from the extension dir)
3. **Run** (per topic) — seeded from the prompt file in `wikischema`, optimization runs on the
   main article PDF only (**no SI**):
   ```bash
   cd mediawiki/extensions/ChemExtension
   php maintenance/optimizeExtractionPrompt.php \
       --topic Photocatalytic_CO2_conversion \
       --prompt-file ../../../wikischema/MediaWiki/Prompt_import_Photocatalytic_CO2_conversion.wiki \
       --iterations 8 --structured --critic 0.6 --export-prompt
   ```
   Each iteration writes `eval/<Topic>/results/metrics.csv` + a matplotlib `trend.png/pdf`
   (X = iteration, Y = metrics). `--export-prompt` writes the winning prompt back into
   `wikischema/MediaWiki/Prompt_import_<Topic>.wiki` — the one artefact that lands in the repo.
4. **Archive the progression** (commit regularly):
   ```bash
   git add wikischema/MediaWiki/Prompt_import_*.wiki "mediawiki/extensions/ChemExtension/eval/*/results/*"
   git commit -m "eval run <date>: trend + optimized prompt"
   ```

**Stop / monitor / long runs:**
- stop: `Ctrl+C` — each finished iteration is already archived in `results/`, so nothing is lost.
- background: `nohup php maintenance/optimizeExtractionPrompt.php … > run.log 2>&1 &` then `kill <pid>` to stop.
- watch progress: `tail -f run.log` or open `eval/<Topic>/results/trend.png`.

The gold set (47 publications) and all 47 PDFs are already in place locally (data is gitignored).

The loop is run with the maintenance script:

```bash
php maintenance/optimizeExtractionPrompt.php --topic Host_Guest_interaction --iterations 5 --dry-run
# when satisfied, write the best prompt back to MediaWiki:Prompt_import_<Topic>:
php maintenance/optimizeExtractionPrompt.php --topic Host_Guest_interaction --iterations 5 --write
```

It requires `$wgOpenAIKey` to be configured (used by `AIClient`).

## Layout

```
eval/
  <Topic_with_underscores>/      # must match the topic category / Prompt_import_<Topic> page name
    gold/
      <name>.json                # one curated publication each (see format below)
    pdfs/
      <file>.pdf                 # the source PDFs referenced from the gold files
    runs/                        # written by the loop: one JSON per iteration
    memory.md                    # written by the loop: human-readable log the optimizer reads back
    best_prompt.txt              # written on --dry-run: the best prompt found
```

The topic directory name (underscored) must equal the topic the import tags, so that the
optimized prompt is written to the matching `MediaWiki:Prompt_import_<Topic>` page.

## Building the gold set from a wiki XML export

If you already curated the publications and investigations in the wiki, export them
(Special:Export) and let the builder turn them into the gold set automatically:

```bash
php maintenance/buildGoldSetFromXml.php \
    --publications publications.xml --investigations investigations.xml --fetch-pdfs
```

It reads the DOI + topic from each publication page and the curated experiments from the
investigation tables (`{{<RowTemplate> |…}}`), writes `eval/<Topic>/gold/<doi>.json`, and — with
`--fetch-pdfs` — downloads the **open-access** PDFs by DOI via Unpaywall into `eval/<Topic>/pdfs/`.

**PDFs that are not open access** are listed at the end; add them manually into the topic's
`pdfs/` folder named `<doi-with-slashes-as-underscores>.pdf` (e.g. from your reference manager or
institutional access). Molecule values keep the wiki's `Molecule:<id>` form, which the scorer
compares directly.

## Gold-standard co-evolution (correcting gold errors)

Manual curation has errors too. When the model — backed by the source document — repeatedly and
confidently disagrees with a gold value, the *gold value* may be the thing that is wrong. So the
gold set co-evolves with the prompt, on evidence:

```bash
# 1) audit the gold against the source PDFs -> suspected errors with quotes
php maintenance/auditGoldSet.php --topic Photocatalytic_CO2_conversion --threshold 0.7
#    writes eval/<Topic>/gold_review.md (human review) and gold_corrections.template.json
# 2) review gold_review.md; copy the CONFIRMED entries into gold_corrections.json
# 3) apply the confirmed corrections back into the gold JSONs
php maintenance/applyGoldCorrections.php --topic Photocatalytic_CO2_conversion   # --dry-run to preview
```

Safety: nothing is changed automatically — the auditor only *flags candidates with evidence*; a
human confirms, and only then are the gold files updated. Molecule references (`Molecule:<id>`) are
never flagged. This keeps the model from silently rewriting its own ground truth, while letting
real curation mistakes get fixed so both the prompt and the gold standard keep improving.

## Gold file format

One JSON file per publication under `<Topic>/gold/`:

```json
{
  "doi": "10.1021/jacs.0c00000",
  "topic": "Host Guest interaction",
  "pdf": "pdfs/jacs_0c00000.pdf",
  "si": ["pdfs/jacs_0c00000_si.pdf"],
  "experiments": [
    { "host": "CB[7]", "host conc": "0.001", "guest": "adamantane-1-carboxylate",
      "guest conc": "0.001", "guest_host_ratio": "1:1", "ka": "1.2e9",
      "kd": "8.3e-10", "deltaG": "-52", "temperature": "298",
      "detection_type": "ITC", "technique": "ITC" }
  ],
  "prose": {
    "Abstract Summary": "The human-written summary text from the curated wiki entry ...",
    "Advances and Special Progress": "..."
  }
}
```

The optional `si` (or `attachments`) array lists supplementary-information PDFs to upload
alongside the article — the experimental data tables often live there.

The optional `prose` field holds the human-written reference text from the curated wiki entry.
It may be a single string or a map of section name → text; it is only used for the (secondary)
prose-similarity metric and can be omitted.

**Important:** the keys in each `experiments` object must be the **row-template parameter names**
(the same column headers the corresponding `MediaWiki:Prompt_import_<Topic>` page asks for), so
that gold values and extracted values share one vocabulary. See the row templates:

- `wikischema/Template/Host_Guest_interaction.wiki`
- `wikischema/Template/EC_conversion_of_CO2.wiki`
- `wikischema/Template/Photocatalytic_CO2_conversion.wiki`

## Metric

**Primary — structured field accuracy** (`ExtractionScorer`, not text similarity):

- extracted experiment rows are greedily matched to gold rows,
- numeric cells (Ka, Kd, ΔG, TON, faradaic efficiency, concentrations, …) match within a
  relative tolerance (`--tolerance`, default 10%),
- molecule cells (catalyst, photosensitizer, host, guest, solvents — see `EvalTopicConfig`) are
  compared by molecule identity via `MoleculeResolver`, so synonyms/abbreviations that resolve to
  the same Molecule page count as a match,
- numeric cells with a known unit (see `EvalTopicConfig::expectedUnits`) are compared unit-aware
  via `UnitConverter` — "1 µM" and "1e-6 M" are treated as equal,
- all other cells match by normalized string equality,
- precision / recall / F1 are micro-averaged over all non-empty cells.

**Unit correctness** — a separate rate reporting how often the extracted value carries a
dimensionally consistent unit for its field (e.g. a potential not reported in a concentration
unit). Computed by `UnitConverter::inFamily` over the fields in `EvalTopicConfig::expectedUnits`.

This is the quantitative "Treffsicherheit" number for the paper.

**Efficiency** — input/output token usage per publication is recorded; `F1 per 1k tokens` is
reported so the optimizer is pushed to stay both accurate and lean. `--token-penalty` lets the
best-prompt selection trade a little F1 for fewer tokens (default 0 = pure F1).

**Sanity (plausibility)** — deterministic, model-independent checks on the extraction alone (no
gold needed): percentages/yields in [0,100], turnover numbers ≥ 0, binding constants > 0,
per-product efficiencies sum to ≤ 100. Reported as a sanity pass-rate and fed to the optimizer.
The rules are derived generically per topic (see "Adding a new topic").

**Critic / confidence** — an optional second pass (`--critic <t>`) re-reads the source and scores
each row's confidence with a supporting quote; the average confidence and the number of flagged
rows are reported. In production import, set `$wgCEExtractionCriticThreshold` to gate: pages whose
average confidence falls below it get a review notice + `[[Category:Needs review]]` instead of
being trusted silently.

**Secondary — prose similarity** — when a gold entry has a `prose` field, the AI prose (response
minus the CSV block) is compared to the reference via embedding cosine similarity
(`ProseSimilarityScorer` + `EmbeddingClient`, model `$wgOpenAIEmbeddingModel`). Disable with
`--no-embeddings`. Kept as a low-weight support signal only.

## CLI options

```
--topic <dir>          (required) topic directory under eval/
--iterations <n>       optimization iterations (default 5)
--tolerance <f>        numeric relative tolerance (default 0.1)
--token-penalty <f>    penalty per 1k tokens/pub when selecting the best prompt (default 0)
--no-embeddings        skip prose similarity (no embedding API calls)
--structured           use structured outputs (JSON schema) instead of CSV-in-prose
--vision <n>           also attach the first n rendered PDF pages as images (needs pdftoppm)
--critic <t>           second-pass critic; rows below confidence t (0..1) are flagged
--prompt-page <name>   prompt page to seed from / write to (default Prompt_import_<Topic>)
--prompt-file <path>   seed the initial prompt from a file instead of the wiki page
--write                write the best prompt back to the prompt page
--dry-run              do not write the wiki page (default)
```

## Paper-ready report (convergence / trends)

Every iteration is recorded under `eval/<Topic>/runs/`. To turn that history into figures and
tables for the paper:

```bash
php maintenance/exportEvalReport.php                      # all topics with runs
php maintenance/exportEvalReport.php --topic Host_Guest_interaction
```

Writes to `eval/<Topic>/report/`:

- `metrics.csv` — tidy data (one row per iteration) for any plotting tool,
- `convergence.svg` — vector line chart of the quality metrics over iterations (shows
  convergence/trend at a glance; F1, precision, recall, unit correctness, sanity, confidence,
  prose similarity),
- `tokens.svg` — efficiency trend (tokens per publication),
- `metrics.tex` — a pgfplots plot + a booktabs summary table to paste straight into the paper,
- `report.md` — short summary (best iteration, start→best Δ, convergence).

When more than one topic has runs, it also writes a cross-topic comparison to `eval/report/`:
`topic_comparison.svg` (best F1 per topic as a bar chart), `topic_comparison.csv` and
`topic_comparison.md` — a ready "results" figure for the paper.

This needs no API key — it only reads the recorded runs.

## Adding a new topic (generic core + per-topic fine-tuning)

The optimizer is topic-agnostic. For a brand-new topic, almost everything is **derived
automatically** from the wiki by `TopicProfile`:

- experiment fields → from the investigation row template,
- molecule columns → from the `{{DisplayMolecule|…}}` wrappers in that template,
- expected units → from the SMW property / default-unit definitions,
- sanity rules → from the unit families + naming heuristics.

To add a topic you only:

1. create `eval/<Topic>/gold/` with curated JSON (+ `pdfs/`),
2. optionally drop `eval/<Topic>/profile.json` to **fine-tune** the derived defaults — no code change.

`profile.json` (all keys optional; override only what the derivation gets wrong):

```json
{
  "form": "My_investigation_form",
  "fields": ["colA", "colB"],
  "moleculeFields": ["catalyst"],
  "expectedUnits": { "colA": { "unit": "M", "family": "concentration" } },
  "sanityRules": {
    "nonNegative": ["colB"], "positive": [], "percentage": [],
    "sumAtMost": [{ "label": "fe", "max": 100, "fields": ["fe__a", "fe__b"] }]
  }
}
```

Precedence: derived (base) < built-in defaults < `profile.json`. Unit families:
`concentration, potential, time, energy_per_mol, wavelength, association, frequency, percent,
current_density, temperature`.

> Note: `runs/`, `memory.md` and `best_prompt.txt` are generated artefacts. Consider git-ignoring
> them once real runs start, to keep the gold set (the curated input) separate from loop output.
