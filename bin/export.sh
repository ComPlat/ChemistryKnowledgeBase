#!/usr/bin/env bash

if [ ! -e $MEDIAWIKI/maintenance/runJobs.php ]; then
    echo ERROR: Environment variable MEDIAWIKI must be properly set.
    echo ERROR: Check setup-XXX.sh for setting environment variables. 
    exit 1
fi

DATE=$(date +%F_%H%M%S)

## export wiki schema
php $MEDIAWIKI/extensions/PageImport/maintenance/WikiExport.php --directory=wikischema$DATE
# php $MEDIAWIKI/extensions/PageImport/maintenance/WikiExport.php --directory=wikischema$DATE --namespace=Attribut,Benutzer,Datei,Formular,Hilfe,Kategorie,Konzept,MediaWiki,ODB,Test,Vorlage

tar cfvz wikischema$DATE.tar.gz wikischema$DATE/
rm -rf wikischema$DATE/

echo Created file: wikischema$DATE.tar.gz