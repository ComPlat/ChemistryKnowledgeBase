#!/usr/bin/env bash

## DEV (local vagrant VM)
export CHEM=/var/www/html
export MW=$CHEM/mediawiki
export MEDIAWIKI=$MW
export WIKISCHEMA=/home/vagrant/wikischema
export WIKIIMAGES=/home/vagrant/wikiimages
export BIN=/home/vagrant/bin

touch $MW/LocalSettings.php
