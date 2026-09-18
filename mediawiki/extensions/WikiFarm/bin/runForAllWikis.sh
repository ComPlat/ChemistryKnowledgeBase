#!/usr/bin/env bash

# ===== Configuration =====
# Directory where this script lives (equivalent to %~dp0)
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Root folder to scan (default: ../mediawiki relative to this script)
ROOT="$SCRIPT_DIR/../../.."

if [ -z "$1" ]; then
    echo "Scriptname is missing, eg. runJobs.sh"
    return 1 2>/dev/null || exit 1
fi

# The script to run for each matching folder
RUN_SCRIPT="$SCRIPT_DIR/$1"

# ===== Validate =====
if [ ! -d "$ROOT" ]; then
    echo "Root folder does not exist: \"$ROOT\""
    exit 1
fi

if [ ! -f "$RUN_SCRIPT" ]; then
    echo "Script to run not found: \"$RUN_SCRIPT\""
    exit 1
fi

# ===== Iterate matching subdirectories: env-farm-* =====
# Enable nullglob so the loop is skipped if no matches are found
shopt -s nullglob

for dir in "$ROOT"/env-farm-*/; do
    # Strip trailing slash and get the basename
    dir="${dir%/}"
    name="$(basename "$dir")"

    echo "=== Running in: $name ==="

    # pushd/popd equivalent using a subshell keeps CWD clean
    if ! pushd "$dir" > /dev/null; then
        echo "Failed to enter directory: \"$dir\""
        exit 1
    fi

    echo "$name"
    PREFIX="env-farm-"

    # Remove prefix only if it is at the start (case-insensitive)
    shopt -s nocasematch
    if [[ "$name" == "$PREFIX"* ]]; then
        OUT="${name#$PREFIX}"
    else
        OUT="$name"
    fi
    shopt -u nocasematch

    echo "$OUT"
    "$RUN_SCRIPT" "$OUT"
    rc=$?

    popd > /dev/null

    if [ "$rc" -ne 0 ]; then
        echo "Script failed in $name with exit code $rc"
        exit "$rc"
    fi
done

echo "Done."
exit 0