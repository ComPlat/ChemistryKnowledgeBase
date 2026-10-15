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

# ===== Validate =====
if [ ! -d "$ROOT" ]; then
    echo "Root folder does not exist: \"$ROOT\""
    exit 1
fi

# Ensure the setup script exists
if [ ! -f "$SCRIPT_DIR/setup.sh" ]; then
    echo 'Configuration file "setup.sh" does not exist. Please create it.'
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
        WIKI="${name#$PREFIX}"
    else
        WIKI="$name"
    fi
    shopt -u nocasematch

    echo "$WIKI"
    if source "$SCRIPT_DIR/setup.sh" "$WIKI"; then
      "$PHP_BIN" $*
    fi
    rc=$?

    popd > /dev/null

    if [ "$rc" -ne 0 ]; then
        echo "Script failed in $name with exit code $rc"
        exit "$rc"
    fi
done

echo "Done."
exit 0