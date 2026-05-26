#!/usr/bin/env python3
"""Plot the optimization metrics over iterations as a matplotlib trend figure.

Reads a metrics CSV (written by the eval loop each iteration) and writes trend.png + trend.pdf.
X axis = iteration round, Y axis = metric score — a curve per metric that grows as the loop
improves. Run automatically by the loop (per iteration) and/or standalone:

    python3 plot_eval_metrics.py <metrics.csv> <out_dir> [topic_title]
"""
import csv, sys, os

import matplotlib
matplotlib.use("Agg")  # headless
import matplotlib.pyplot as plt

# 0..1 quality metrics drawn together (column -> label)
QUALITY = [
    ("f1_best", "F1 (best so far)"),
    ("f1", "F1"),
    ("precision", "Precision"),
    ("recall", "Recall"),
    ("unitCorrectness", "Unit correctness"),
    ("sanityPassRate", "Sanity pass rate"),
    ("avgConfidence", "Critic confidence"),
    ("proseSimilarity", "Prose similarity"),
]

def main():
    if len(sys.argv) < 3:
        print("usage: plot_eval_metrics.py <metrics.csv> <out_dir> [title]", file=sys.stderr)
        return 1
    csv_path, out_dir = sys.argv[1], sys.argv[2]
    title = sys.argv[3] if len(sys.argv) > 3 else "Extraction metrics"
    os.makedirs(out_dir, exist_ok=True)

    iters, series = [], {k: [] for k, _ in QUALITY}
    tokens = []
    with open(csv_path) as f:
        for row in csv.DictReader(f):
            try:
                it = int(row["iteration"])
            except (KeyError, ValueError):
                continue
            iters.append(it)
            for k, _ in QUALITY:
                v = row.get(k, "")
                series[k].append(float(v) if v not in ("", None) else None)
            t = row.get("tokensPerPub", "")
            tokens.append(float(t) if t not in ("", None) else None)

    if not iters:
        print("no data rows", file=sys.stderr)
        return 1

    fig, ax = plt.subplots(figsize=(8, 5))
    for k, label in QUALITY:
        xs = [it for it, v in zip(iters, series[k]) if v is not None]
        ys = [v for v in series[k] if v is not None]
        if ys:
            ax.plot(xs, ys, marker="o", label=label)
    ax.set_xlabel("Iteration round")
    ax.set_ylabel("Score")
    ax.set_ylim(0, 1)
    ax.set_xticks(sorted(set(iters)))
    ax.set_title(title)
    ax.grid(True, alpha=0.3)
    ax.legend(loc="lower right", fontsize=8)
    fig.tight_layout()
    fig.savefig(os.path.join(out_dir, "trend.png"), dpi=150)
    fig.savefig(os.path.join(out_dir, "trend.pdf"))
    plt.close(fig)

    # efficiency trend (tokens per publication) as a separate figure
    xs = [it for it, v in zip(iters, tokens) if v is not None]
    ys = [v for v in tokens if v is not None]
    if ys:
        fig2, ax2 = plt.subplots(figsize=(8, 4))
        ax2.plot(xs, ys, marker="s", color="#555")
        ax2.set_xlabel("Iteration round")
        ax2.set_ylabel("Tokens / publication")
        ax2.set_xticks(sorted(set(iters)))
        ax2.set_title(title + " — efficiency")
        ax2.grid(True, alpha=0.3)
        fig2.tight_layout()
        fig2.savefig(os.path.join(out_dir, "tokens.png"), dpi=150)
        fig2.savefig(os.path.join(out_dir, "tokens.pdf"))
        plt.close(fig2)

    print("wrote trend.png/trend.pdf to " + out_dir)
    return 0

if __name__ == "__main__":
    sys.exit(main())
