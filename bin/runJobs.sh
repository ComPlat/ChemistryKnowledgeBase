#!/usr/bin/env bash
# will be executed by a cron-job (once a minute)

## get MW directory
BASEDIR=`cd "$(dirname "$0")" && pwd`
MEDIAWIKI=$BASEDIR/../mediawiki

if [ ! -e $MEDIAWIKI/maintenance/runJobs.php ]; then
  # assume we are in the VM
  MEDIAWIKI=/var/www/html/mediawiki
fi

## get USER for running the jobs from script argument
if [[ $# -gt 0 ]]; then 
  USER=$1
else
  # default user, valid for staging
  USER=apache
fi

RUNNING=$(ps aux | grep /maintenance/runJobs.php | grep "maxjobs=999")
if [ -z "$RUNNING" ]; then
  su $USER -c "php $MEDIAWIKI/maintenance/runJobs.php --maxjobs=999"
fi
