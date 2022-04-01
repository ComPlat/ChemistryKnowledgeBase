#!/usr/bin/env bash


if [ ! -e $MEDIAWIKI/maintenance/runJobs.php ]; then
   if [ ! -e ../mediawiki/maintenance/runJobs.php ]; then
       echo Error: runJobs.php script not found
       exit 1
   else
    export RUNJOBSCRIPT=../mediawiki/maintenance/runJobs.php
   fi
else
export RUNJOBSCRIPT=$MEDIAWIKI/maintenance/runJobs.php
fi



if [ -z "$1" ]
  then
    echo "No wikiId supplied"
    exit
fi

export REQUEST_URI=/$1/mediawiki
echo "Running job for wiki $1"
php $RUNJOBSCRIPT
