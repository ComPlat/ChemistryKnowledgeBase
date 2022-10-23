#!/usr/bin/env bash

if [ -z "$1" ]
  then
    echo "No wiki path supplied"
    exit
fi

export REQUEST_URI=/main/mediawiki
php $1/extensions/WikiFarm/maintenance/runJobsForAllWikis.php