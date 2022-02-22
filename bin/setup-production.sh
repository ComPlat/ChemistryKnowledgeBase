#!/usr/bin/env bash

## PROD
export CHEM=/opt/httpd/vhosts/odb.zh.ch/htdocs/odbwiki
export MW=$CHEM/mediawiki
export MEDIAWIKI=$MW
export WIKISCHEMA=$CHEM/wikischema
export WIKIIMAGES=$CHEM/wikiimages
export BIN=$CHEM/bin

touch $MW/LocalSettings.php
