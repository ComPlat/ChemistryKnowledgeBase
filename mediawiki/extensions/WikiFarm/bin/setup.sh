#!/usr/bin/env bash

# Create a copy of this file, named setup.sh, which will be sourced by all other shell scripts in this folder.
# Usage: source ./setup.sh <wiki-ID>

if [ -z "$1" ]; then
    echo "Wiki-ID is missing. Pass it as an argument, e.g.: source ./setup.sh <wiki-ID>"
    return 1 2>/dev/null || exit 1
fi

export MW=/var/www/html/mediawiki
export WIKI="$1"
export DB_USER=root
export DB_PASS=vagrant
export MYSQL_BIN=/usr/bin/mariadb
export MYSQL_DUMP_BIN=/usr/bin/mariadb-dump
export PHP_BIN=/usr/bin/php

export DB_NAME="chem${1}"