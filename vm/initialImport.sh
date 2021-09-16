#!/usr/bin/env bash

export MEDIAWIKI=/var/www/html/mediawiki
export WIKISCHEMA=/home/vagrant/wikischema

echo '#################### Run all Jobs'
date
/home/vagrant/bin/runJobs.sh 


## ideally stop CRON jobs and EnhancedRetrieval here


echo '#################### Import Wiki-Schema'
date
php $MEDIAWIKI/extensions/WikiImportExport/maintenance/WikiImport.php --directory=$WIKISCHEMA


echo '#################### Rebuild images, links, category pages, and recent changes'
date
php $MEDIAWIKI/maintenance/rebuildImages.php --missing
php $MEDIAWIKI/maintenance/rebuildrecentchanges.php
php $MEDIAWIKI/maintenance/refreshLinks.php


echo '#################### Run all Jobs'
date
/home/vagrant/bin/runJobs.sh 


echo '#################### Rebuild all semantic data, possibly not needed after refreshLinks?!'
date
php $MEDIAWIKI/extensions/SemanticMediaWiki/maintenance/rebuildData.php


echo '#################### Run all Jobs'
date
/home/vagrant/bin/runJobs.sh 


## enable EnhancedRetrieval again


echo '#################### Rebuild full-text index for EnhancedRetrieval'
date
php $MEDIAWIKI/extensions/EnhancedRetrieval/maintenance/updateSOLR.php -v


echo '#################### Run all Jobs'
date
/home/vagrant/bin/runJobs.sh 


## enable CRON here again


echo '#################### Finished initial import'
date
