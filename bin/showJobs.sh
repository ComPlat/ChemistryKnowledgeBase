#!/usr/bin/env bash

if [ ! -e $MEDIAWIKI/maintenance/runJobs.php ]; then
    echo Error: Environment variable MEDIAWIKI must be properly set.
    echo Error: Check setup-XXX.sh for setting environment variables.
    exit 1
fi

php $MEDIAWIKI/maintenance/showJobs.php
echo "----------"
php $MEDIAWIKI/maintenance/showJobs.php --group | sort
echo "----------"
#php $MEDIAWIKI/maintenance/showJobs.php --list | sort
