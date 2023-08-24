#!/usr/bin/env bash

if [ -f env.sh ]; then
    source env.sh
fi

if [ ! -e $MEDIAWIKI/maintenance/update.php ]; then
   if [ ! -e ../mediawiki/maintenance/update.php ]; then
       echo Error: update.php script not found
       exit 1
   else
    export RUNJOBSCRIPT=../mediawiki/maintenance/update.php
   fi
else
export RUNJOBSCRIPT=$MEDIAWIKI/maintenance/update.php
fi



if [ -z "$1" ]
  then
    echo "No wikiId supplied"
    exit
fi

export REQUEST_URI=/$1/mediawiki
echo "Running setup for wiki $1"
php $RUNJOBSCRIPT --quick
