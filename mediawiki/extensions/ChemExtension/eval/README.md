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
  "experiments": [
    { "host": "CB[7]", "host conc": "0.001", "guest": "adamantane-1-carboxylate",
      "guest conc": "0.001", "guest_host_ratio": "1:1", "ka": "1.2e9",
      "kd": "8.3e-10", "deltaG": "-52", "temperature": "298",
      "detection_type": "ITC", "technique": "ITC" }
  ]
}
```

**Important:** the keys in each `experiments` object must be the **row-template parameter names**
(the same column headers the corresponding `MediaWiki:Prompt_import_<Topic>` page asks for), so
that gold values and extracted values share one vocabulary. See the row templates:

- `wikischema/Template/Host_Guest_interaction.wiki`
- `wikischema/Template/EC_conversion_of_CO2.wiki`
- `wikischema/Template/Photocatalytic_CO2_conversion.wiki`

## Metric

`ExtractionScorer` does a structured, field-level comparison (not text similarity):

- extracted experiment rows are greedily matched to gold rows,
- numeric cells (Ka, Kd, ΔG, TON, faradaic efficiency, concentrations, …) match within a
  relative tolerance (`--tolerance`, default 10%),
- all other cells match by normalized string equality,
- precision / recall / F1 are micro-averaged over all non-empty cells.

This is the quantitative "Treffsicherheit" number for the paper.

> Note: `runs/`, `memory.md` and `best_prompt.txt` are generated artefacts. Consider git-ignoring
> them once real runs start, to keep the gold set (the curated input) separate from loop output.
