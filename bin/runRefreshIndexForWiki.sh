#!/usr/bin/env bash

if [ -f env.sh ]; then
    source env.sh
fi

if [ ! -e $MEDIAWIKI/extensions/ChemExtension/maintenance/refreshChemFormIndex.php ]; then
   if [ ! -e ../mediawiki/extensions/ChemExtension/maintenance/refreshChemFormIndex.php ]; then
       echo Error: runRefreshIndexForAllWikis.php script not found
       exit 1
   else
    export RUNJOBSCRIPT=../mediawiki/extensions/ChemExtension/maintenance/refreshChemFormIndex.php
   fi
else
export RUNJOBSCRIPT=$MEDIAWIKI/extensions/ChemExtension/maintenance/refreshChemFormIndex.php
fi



if [ -z "$1" ]
  then
    echo "No wikiId supplied"
    exit
fi

export REQUEST_URI=/$1/mediawiki
echo "Running job for wiki $1"
php $RUNJOBSCRIPT $2
