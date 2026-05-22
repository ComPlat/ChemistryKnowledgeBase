# Extraction evaluation & self-optimizing prompt loop

This directory holds the **gold set** (manually curated reference extractions) and the
**memory** of the self-optimizing extraction loop, per topic.

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
--prompt-page <name>   prompt page to seed from / write to (default Prompt_import_<Topic>)
--prompt-file <path>   seed the initial prompt from a file instead of the wiki page
--write                write the best prompt back to the prompt page
--dry-run              do not write the wiki page (default)
```

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
