#!/usr/bin/env bash

RET=$(sudo id -u)
if [ $RET -ne 0 ]
  then echo "Can not run sudo"
  exit
fi

# Param1: wikiId

if [ -z "$1" ]
  then
    echo "No wikiId supplied"
    exit
fi

export BASE=/var/www/html
export MEDIAWIKI=/var/www/html/mediawiki

# Check for path
WIKI=""
if [ -e $BASE/$1 ]
then
WIKI="wiki"
fi

# Check for DB
export DB_USER=admin
export DB_PASS=vagrant
export DB_USER_ADMIN=admin
export DB_PASS_ADMIN=vagrant
if [ -e $MEDIAWIKI/env.sh ]
then
    source $MEDIAWIKI/env.sh
fi

DB=""
mysql -u $DB_USER_ADMIN -p$DB_PASS_ADMIN -e "use chem$1;"
if [ $? -eq 0 ]
then
DB="db"
fi

# Check for SOLR
SOLR=""
if [ -e /var/solr/data/$1 ]
then
SOLR="solr"
fi

echo "$WIKI,$DB,$SOLR"