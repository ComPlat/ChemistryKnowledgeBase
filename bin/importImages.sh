#!/usr/bin/env bash

if [ ! -e $MEDIAWIKI/maintenance/runJobs.php ]; then
    echo ERROR: Environment variable MEDIAWIKI must be properly set.
    echo ERROR: Check setup-XXX.sh for setting environment variables. 
    exit 1
fi

if [ ! -e $WIKIIMAGES/0 ]; then
    echo ERROR: Environment variable WIKIIMAGES must be properly set.
    echo ERROR: Check setup-XXX.sh for setting environment variables. 
    exit 1
fi

php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/0
php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/1
php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/2
php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/3
php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/4
php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/5
php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/6
php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/7
php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/8
php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/9
php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/a
php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/b
php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/c
php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/d
php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/e
php $MEDIAWIKI/maintenance/importImages.php --search-recursively --overwrite $WIKIIMAGES/f

php $MEDIAWIKI/maintenance/rebuildImages.php 

#php $MEDIAWIKI/maintenance/rebuildrecentchanges.php
#php $MEDIAWIKI/maintenance/refreshLinks.php

