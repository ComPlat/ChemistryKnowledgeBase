#!/usr/bin/env bash

if [ ! -e $MEDIAWIKI/maintenance/runJobs.php ]; then
    echo ERROR: Environment variable MEDIAWIKI must be properly set.
    echo ERROR: Check setup-XXX.sh for setting environment variables.
    exit 1
fi

if [ ! -e $WIKISCHEMA/Main ]; then
    echo ERROR: Environment variable WIKISCHEMA must be properly set.
    echo ERROR: Check setup-XXX.sh for setting environment variables. 
    exit 1
fi

php $MEDIAWIKI/extensions/PageImport/maintenance/WikiImport.php --verbose --directory=$WIKISCHEMA
#php $MEDIAWIKI/extensions/PageImport/maintenance/WikiImport.php --verbose --directory=$WIKISCHEMA --namespace=Attribut,Vorlage,Formular,Datei,Hilfe,Kategorie,Konzept,MediaWiki,ODB,Test

touch $MEDIAWIKI/LocalSettings.php

php $MEDIAWIKI/maintenance/refreshLinks.php

BASEDIR=`cd "$(dirname "$0")" && pwd`
$BASEDIR/runJobs2.sh 
