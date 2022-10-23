#!/usr/bin/env bash

if [ ! -e $MEDIAWIKI/maintenance/runJobs.php ]; then
    echo Error: Environment variable MEDIAWIKI must be properly set.
    echo Error: Check setup-XXX.sh for setting environment variables.
    exit 1
fi

# check the status of the SOLR server
# curl "http://localhost:8983/solr/admin/cores?action=STATUS&wt=json"
# curl "http://localhost:8080/solr/admin/cores?action=STATUS&wt=json"
# curl "http://localhost:8080/solr/admin/system?action=STATUS&wt=json"

# if you want to clear the index first
# curl "http://localhost:8983/solr/mw/update?stream.body=%3Cdelete%3E%3Cquery%3E*:*%3C/query%3E%3C/delete%3E&commit=true"
# curl "http://localhost:8080/solr/collection1/update?stream.body=%3Cdelete%3E%3Cquery%3E*:*%3C/query%3E%3C/delete%3E&commit=true"

COUNTER=1
while [  $COUNTER -lt 111100 ]; do
  let ENDCOUNTER=COUNTER+99 
  echo $(date +%H:%M:%S) updateSOLR from $COUNTER to $ENDCOUNTER
  php $MEDIAWIKI/extensions/EnhancedRetrieval/maintenance/updateSOLR.php -v    -s $COUNTER -e $ENDCOUNTER
  let COUNTER=COUNTER+100 
done
