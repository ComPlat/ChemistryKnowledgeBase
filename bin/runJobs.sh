#!/usr/bin/env bash

if [ ! -e $MEDIAWIKI/maintenance/runJobs.php ]; then
    echo Error: Environment variable MEDIAWIKI must be properly set.
    echo Error: Check setup-XXX.sh for setting environment variables.
    exit 1
fi

NUM=100
jobCount=0
openJobs=$( php $MEDIAWIKI/maintenance/showJobs.php )
while [ $openJobs -gt 0 ]; do
  echo $(date +%H:%M:%S)
  echo $(date +%H:%M:%S)
  echo $(date +%H:%M:%S) $openJobs "open jobs left ----------------------------------------------" $jobCount "jobs executed"
  php $MEDIAWIKI/maintenance/showJobs.php --group | sort
  echo $(date +%H:%M:%S) " -------------------------------------------------------------"
  php $MEDIAWIKI/maintenance/runJobs.php --maxjobs=$NUM --nothrottle
  jobCount=$((jobCount + NUM))
  openJobs=$( php $MEDIAWIKI/maintenance/showJobs.php )
done
echo $(date +%H:%M:%S) "~"$jobCount "jobs executed ----------------------------------------------"
