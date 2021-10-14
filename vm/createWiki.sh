#!/usr/bin/env bash

# Param1: wikiId
# Param2: wiki name

export BASE=/var/www/html
export MEDIAWIKI=/var/www/html/mediawiki

echo 'Create www folder...'
sudo mkdir $BASE/$1
sudo mkdir $BASE/$1/images
sudo chmod 777 $BASE/$1/images
sudo ln -s $MEDIAWIKI $BASE/$1
sudo sh -c "echo '{\"name\":\"$2\"}' >> $BASE/$1/$1.json"
echo 'done.'

echo 'Create SOLR core...'
sudo su solr -c "/opt/solr/bin/solr create_core -c $1 -d /vagrant/solrcore/conf"
echo 'done.'

echo 'Create database...'
mysql -u root -pvagrant -e "CREATE DATABASE chem$1;"
mysql -u root -pvagrant -e "GRANT ALL PRIVILEGES ON chem$1.* TO 'root'@'%' IDENTIFIED BY 'vagrant';"

mysql -u root -pvagrant --database=chem$1 < $MEDIAWIKI/database.sql
export REQUEST_URI="/$1/mediawiki"
php $MEDIAWIKI/maintenance/update.php --quick
echo 'done.'

echo '#################### Import wiki schema'
source /vagrant/initialImport.sh

