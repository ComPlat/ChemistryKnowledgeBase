#!/usr/bin/env bash

# This script will execute Wiki jobs for all Wikis from the argument list
# e.g. ./runJobs.sh sarstedtwiki sap hr foo

# Exit if no php processes are currently running
if pgrep -x php > /dev/null; then
    exit 0
fi

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Ensure the setup script exists
if [ ! -f "$SCRIPT_DIR/setup.sh" ]; then
    echo 'Configuration file "setup.sh" does not exist. Please create it.'
    exit 1
fi

# Iterate over all parameters and source setup.sh + run runJobs.php for each Wiki, one by one
for wiki in "$@"; do
    # Source setup.sh so exported variables (PHP_BIN, MEDIAWIKI/MW, etc.) are available in this shell
    if source "$SCRIPT_DIR/setup.sh" "$wiki"; then
        "$PHP_BIN" "$MW/maintenance/update.php" --quick
    fi
done
