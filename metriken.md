# Metriken der selbst-optimierenden Extraktions-Pipeline

**Eine-Satz-Zusammenfassung**
Wir optimieren gegen ein **Composite aus Datenqualität (F1 gegen kuratiertes Gold)** und **Layout-Vollständigkeit (alle 7 Wiki-Sektionen)** — multiplikativ verknüpft, sodass der Optimizer weder Daten erfinden noch das Wiki-Layout kaputt machen kann, um seinen Score zu steigern.

```
composite = F1 × Layout_Score          (multiplikativer Gate)
                              ↑                ↑
                  Daten gegen Gold     Pflicht-Sektionen vorhanden + substantiell
```

---

## 1. Primär-Metriken (immer aktiv)

### 1.1 F1 (Hauptmetrik der Datenqualität)
- **Was**: Geometrisches Mittel aus Precision und Recall über alle Feldwerte aller Experimente.
- **Wie**: Greedy-Matching jeder extrahierten Tabellenzeile gegen die ähnlichste Gold-Zeile, dann zellweiser Vergleich.
- **Vergleichsregeln**:
  - **Numerische Toleranz ±10 %** — z. B. `TON 850` vs `845` = Treffer.
  - **Einheiten-aware**: µM/mM/M, h/min/s, h⁻¹/min⁻¹/s⁻¹, °C, nm, % werden vor dem Vergleich konvertiert.
  - **Order-insensitive Ratios** — `4:1` == `1:4` (Solvent-Mix).
  - **Moleküle ausgeklammert** — extrahiert, aber nicht gescored (braucht die SMW-Datenbank des Wikis).
- **Ausgabe**: Zahl in [0, 1].
- **Beispiel**: `F1 = 0.5102` heißt: rund die Hälfte der Gold-Zellen wurde korrekt + im richtigen Feld + in der richtigen Einheit getroffen.

### 1.2 Precision
- **Was**: Anteil der extrahierten Werte, die korrekt sind (= Wahrheitsgehalt).
- **Wie**: TP / (TP + FP).
- **Ausgabe**: [0, 1]. Niedrig = Modell „halluziniert" / erfindet Werte.

### 1.3 Recall
- **Was**: Anteil der Gold-Werte, die der Extractor findet (= Vollständigkeit).
- **Wie**: TP / (TP + FN).
- **Ausgabe**: [0, 1]. Der Haupthebel des Optimizer-Gewinns: `0.29 → 0.62` zwischen Original-Live und Optimizer-Gewinner.

### 1.4 Layout / Structure Score (neu, der Layout-Gate)
- **Was**: Sind alle Pflicht-Wiki-Sektionen mit Substanz vorhanden?
- **Pflicht-Sektionen für Photocat**:
  1. Abstract Summary
  2. Advances and Special Progress
  3. Additional Remarks
  4. Content of the Published Article in Detail
  5. Catalyst
  6. Photosensitizer
  7. Investigation
- **Wie**:
  - Jede Sektion wird im Modell-Output gesucht (Wiki-Heading, Markdown-Heading oder Klartext-Linie, case-insensitive).
  - Prosa-Sektionen müssen **≥ 20 Wörter** Substanz hinter dem Heading haben — sonst zählt sie als „fehlt".
  - **Investigation** ist Sonderfall: muss einen ```csv-Block enthalten **plus** mindestens Header + 1 Daten-Zeile (Wortzahl irrelevant, weil Daten).
- **Ausgabe**: Anteil vorhandener Sektionen, [0, 1].
  - `7/7 = 1.00` → alle Sektionen sauber
  - `0/7 = 0.00` → reines CSV ohne Layout (was der Optimizer-Gewinner aus dem Vorlauf war)
- **Pro Topic konfigurierbar** über `REQUIRED_SECTIONS_BY_TOPIC` im Code oder `eval/<topic>/profile.json`.

### 1.5 Composite (was tatsächlich optimiert wird)
- **Formel**: `composite = F1 × Layout_Score`
- **Wirkung**: Multiplikativer Gate.
  - Layout = 1.0 → composite = F1 (volle Wertung).
  - Layout = 0.5 → composite = ½ F1 (halbe Wertung).
  - Layout = 0.0 → composite = 0 (disqualifiziert — egal wie gut die Daten sind).
- **Zweck**: Der Optimizer kann nicht mehr „Sektionen wegwerfen → Tokens sparen → besseres F1 vortäuschen". Beides muss erfüllt sein.

### 1.6 Tokens / Publikation
- **Was**: Effizienz-Metrik (Kosten + Latenz).
- **Wie**: Σ(Input + Output Tokens) / Anzahl Paper.
- **Ausgabe**: Integer-Wert (typisch 18 000–35 000 Tokens/Paper bei o3).
- **Wozu**: Sieht man, ob der optimierte Prompt zugleich günstiger geworden ist (im Photocat-Proof war er es: −4 %).

---

## 2. Optionale Metriken (Flag-aktiviert)

### 2.1 Groundedness / Halluzinationsrate (`--ground`)
- **Was**: Anteil der extrahierten Werte, die im PDF tatsächlich nachweisbar sind.
- **Wie**: Zweiter API-Pass je Paper, der das Modell als Critic fragt: „Steht jeder dieser Werte explizit in dem Paper?"
- **Ausgabe**:
  - `groundedness ∈ [0, 1]` — wieviele Werte sind belegt.
  - `hallucination = 1 − groundedness` — wieviele Werte sind erfunden / nicht belegt.
- **Kostet**: ~2× Tokens (zweiter Pass pro Paper).
- **Anwendung**: für „perfekte Extraktion ohne Halluzination" als hartes Akzeptanzkriterium.

### 2.2 Field-Hints (`--field-hints`)
- **Was**: Few-Shot-Beispielwerte je Feld aus **anderen** Gold-Papern (leakage-frei — niemals aus dem aktuell extrahierten Paper).
- **Wozu**: Hilft dem Modell, das Format jeder Spalte zu erkennen (z. B. „cat conc ist eine Zahl in µM, nicht ein Bereich").

---

## 3. Diagnose-Metriken (nicht für Optimierung — für die Fehleranalyse)

| Diagnose | Inhalt |
|---|---|
| **Per-Feld-Recall** | Welche Spalten systematisch fehlen — z. B. `cat conc: 3/10 — schwach` |
| **Mismatch-Beispiele** | Konkrete Fehler — z. B. `expected '50 µM', got '50 mM'` |
| **Layout-Missing-Liste** | Welche Sektionen pro Paper gefehlt haben |
| **Zeilen-Statistik** | Extrahierte vs Gold-Zeilen pro Paper (Erkennung von Row-Undercount) |

Diese Felder fließen automatisch in den Verbesserungs-Prompt der nächsten Iteration — der Optimizer sieht seine eigenen Fehler und lernt daraus.

---

## 4. Was die Pipeline pro Iteration ausgibt

### Log (stdout)
```
=== iteration 2/6 ===
  10.1002/anie.201809084: matched 30/30 gold cells, rows 3/3, 18639 tokens, layout 7/7
  10.1002/cctc.201500494: matched 95/266 gold cells, rows 19/38, 24092 tokens, layout 7/7
  ...
  AGG F1=0.5102 P=0.4342 R=0.6186 layout=1.000 composite=0.5102 tok/pub=22779 | best F1=0.5102 composite=0.5102
  improving prompt (from best so far) ...
```

### Dateien (im jeweiligen Topic-Ordner)
```
eval/Photocatalytic_CO2_conversion/
├── results/
│   ├── metrics.csv              Pro-Iter-Zeile: iteration, f1, f1_best, precision, recall,
│   │                            groundedness, hallucination, layoutScore, composite,
│   │                            composite_best, tokensPerPub
│   ├── trend.png + trend.pdf    Matplotlib-Plot aller Metriken über die Iterationen
│   ├── tokens.png + tokens.pdf  Effizienz-Trend (Tokens/Paper)
│   ├── diagnostics.json         Per-Paper-Details: TP, FP, Layout-Missing, Mismatches
│   ├── summary.md               Per-Feld-Recall-Tabelle, "worst first"
│   ├── metrics.tex              Paper-fertige LaTeX-Snippets (pgfplots + booktabs)
│   ├── prompts/iter_<n>.txt     **Jeder benutzte Prompt** archiviert
│   └── best_prompt.txt          Der Gewinner-Prompt (= bester composite)
└── baseline_handversion/        Hand-Version als A/B-Referenz
    └── prompt.wiki
```

### Beispiel `metrics.csv` (eine Zeile pro Iteration)
```csv
iteration,f1,f1_best,precision,recall,groundedness,hallucination,layoutScore,composite,composite_best,tokensPerPub
1,0.4733,0.4733,0.3995,0.5805,,,1.0000,0.4733,0.4733,21135
2,0.5102,0.5102,0.4342,0.6186,,,1.0000,0.5102,0.5102,22779
```

---

## 5. Bisheriger Stand auf Photocat (10 Paper, kuratiertes Gold)

| Prompt | F1 | Layout | Composite | Tokens/Paper |
|---|---:|---:|---:|---:|
| **Original Live-Prompt** | 0.3323 | 1.00 | **0.3323** | 23 772 |
| **Optimizer-Gewinner v1** (CSV-only) | 0.5102 | 0.00 | **0.0000** *(disqualifiziert)* | 22 779 |
| **Hand-Version** (live im Wiki) | nicht gemessen | 1.00 | nicht gemessen | – |
| **Optimizer-Gewinner v2** (mit Layout-Gate) | offen | offen | offen | offen |

Die letzte Zeile ist der nächste Lauf — Hand-Version als Seed, Optimizer darf nur **innerhalb** des Layouts optimieren. Ergebnis dieser Messung entscheidet, was final ins Wiki geht.

---

## 6. Warum dieses Metrik-Design

- **F1 allein reicht nicht** — Modell kann Sektionen weglassen, um Tokens zu sparen, ohne dass F1 leidet (Wiki-Seite ist dann aber kaputt).
- **Layout allein reicht nicht** — Modell kann alle Sektionen erzeugen, aber die Daten erfinden (F1 sinkt, Layout=1).
- **Composite (× statt +)** — beide müssen erfüllt sein; eine 0 in einer Achse setzt das Ergebnis auf 0. Kein Trading-off möglich.
- **Numerische Toleranz + Einheiten** — verhindert, dass die Loop sich auf „108 h⁻¹" festlegt, wenn das Paper „1.8 min⁻¹" schreibt (identischer Wert in unterschiedlicher Notation).
- **Topic-agnostisch** — `REQUIRED_SECTIONS_BY_TOPIC` ist ein Dict; neue Topics bekommen ihre eigene Sektionen-Liste, der Rest läuft generisch.
