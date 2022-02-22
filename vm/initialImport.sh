#!/usr/bin/env bash

export MW=/var/www/html/mediawiki
export MEDIAWIKI=$MW
export WIKISCHEMA=/home/vagrant/wikischema
export WIKIIMAGES=/home/vagrant/wikiimages
export BIN=/home/vagrant/bin

printf "\n\n\n#################### Run all Jobs\n"
date
$BIN/runJobs2.sh 


## ideally stop CRON jobs and EnhancedRetrieval here


printf "\n\n\n#################### Import Wiki-Schema\n"
date
php $MEDIAWIKI/extensions/WikiImportExport/maintenance/WikiImport.php --directory=$WIKISCHEMA


#printf "\n\n\n#################### Import Wiki-Images\n"
#date
#php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/0
#php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/1
#php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/2
#php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/3
#php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/4
#php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/5
#php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/6
#php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/7
#php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/8
#php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/9
#php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/a
#php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/b
#php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/c
#php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/d
#php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/e
#php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/f
printf "\n\n\n#################### Rebuild images, links, category pages, and recent changes\n"
date
php $MEDIAWIKI/maintenance/rebuildImages.php --missing
php $MEDIAWIKI/maintenance/rebuildrecentchanges.php
php $MEDIAWIKI/maintenance/refreshLinks.php


printf "\n\n\n#################### Run all Jobs 1\n"
date
php $MEDIAWIKI/maintenance/runJobs.php --type=smw.changePropagationDispatch
php $MEDIAWIKI/maintenance/runJobs.php --type=smw.changePropagationClassUpdate
php $MEDIAWIKI/maintenance/runJobs.php --type=smw.changePropagationUpdate
php $MEDIAWIKI/maintenance/runJobs.php --type=smw.propertyStatisticsRebuild
php $MEDIAWIKI/maintenance/runJobs.php --type=smw.entityIdDisposer
$BIN/runJobs2.sh 


printf "\n\n\n#################### Rebuild all semantic data, possibly not needed after refreshLinks?!\n"
date
php $MEDIAWIKI/extensions/SemanticMediaWiki/maintenance/rebuildData.php


printf "\n\n\n#################### Run all Jobs 2\n"
date
php $MEDIAWIKI/maintenance/runJobs.php --type=smw.changePropagationDispatch
php $MEDIAWIKI/maintenance/runJobs.php --type=smw.changePropagationClassUpdate
php $MEDIAWIKI/maintenance/runJobs.php --type=smw.changePropagationUpdate
php $MEDIAWIKI/maintenance/runJobs.php --type=smw.propertyStatisticsRebuild
php $MEDIAWIKI/maintenance/runJobs.php --type=smw.entityIdDisposer
$BIN/runJobs2.sh 


## enable EnhancedRetrieval again


printf "\n\n\n#################### Rebuild full-text index for EnhancedRetrieval\n"
date
# curl "http://localhost:8893/solr/mw/update?stream.body=%3Cdelete%3E%3Cquery%3E*:*%3C/query%3E%3C/delete%3E&commit=true"
php $MEDIAWIKI/extensions/EnhancedRetrieval/maintenance/updateSOLR.php -v


printf "\n\n\n#################### Run all Jobs 3\n"
date
$BIN/runJobs2.sh 


## enable CRON here again


printf "\n\n\n#################### Finished initial import\n"
date
