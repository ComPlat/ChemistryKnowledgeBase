#!/usr/bin/env bash

if [ -f env.sh ]; then
    source env.sh
fi

if [ ! -e $MEDIAWIKI/extensions/ChemExtension/maintenance/runImportInMainContext.php ]; then
   if [ ! -e ../extensions/ChemExtension/maintenance/runImportInMainContext.php ]; then
       echo Error: runImportInMainContext.php script not found
       exit 1
   else
    export RUNJOBSCRIPT=../extensions/ChemExtension/maintenance/runImportInMainContext.php
   fi
else
export RUNJOBSCRIPT=$MEDIAWIKI/extensions/ChemExtension/maintenance/runImportInMainContext.php
fi



if [ -z "$1" ]
  then
    echo "No import file specified"
    exit
fi

if [ -z "$2" ]
  then
    echo "No page title specified"
    exit
fi

export REQUEST_URI=/main/mediawiki
php $RUNJOBSCRIPT --file=$1 --title=$2
