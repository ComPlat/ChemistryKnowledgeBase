#!/usr/bin/env bash

# Param1: wikiId

export BASE=/var/www/html
export MEDIAWIKI=/var/www/html/mediawiki

if [ -z "$1" ]
  then
    echo "No argument supplied"
    exit
fi

echo 'Delete www folder...'
sudo rm -rf $BASE/$1
echo 'done.'

echo 'Delete SOLR core...'
sudo su solr -c "/opt/solr/bin/solr delete -c $1"
echo 'done.'

echo 'Delete database...'
mysql -u admin -pvagrant -e "DROP DATABASE chem$1;"
