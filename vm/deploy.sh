#!/usr/bin/env bash

export MEDIAWIKI=/var/www/html/mediawiki

# Install Powersearch
sudo mkdir /opt/powersearch
sudo mv /home/vagrant/powersearch-2.3.0.zip /opt/powersearch
cd /opt/powersearch
sudo unzip powersearch-2.3.0.zip
sudo unzip solr-8.3.0.zip
sudo sh install_solr_service.sh solr-8.3.0.zip
sudo cp -r mw /var/solr/data
sudo chown -R solr:solr /var/solr/data/mw
sudo service solr restart

#################### Drop DB -- if desired
# Be very careful. This step cannot be undone.
# If you want to completely remove all contents and all users etc. from 
# your Wiki-DB uncomment the next line before running this script.
#mysql -u root -pvagrant -e "DROP DATABASE IF EXISTS chemwiki;"


echo '#################### Initialize MySQL-DB'
mysqladmin -u root password vagrant
mysql -u root -pvagrant -e "CREATE DATABASE chemwiki;"
mysql -u root -pvagrant -e "GRANT ALL PRIVILEGES ON chemwiki.* TO 'root'@'%' IDENTIFIED BY 'vagrant';"

mysql -u root -pvagrant --database=chemwiki < $MEDIAWIKI/database.sql
php $MEDIAWIKI/maintenance/update.php --quick

echo '#################### Initialize extensions'
php $MEDIAWIKI/extensions/SemanticMediaWiki/maintenance/setupStore.php 

echo '#################### Import wiki schema'
source /vagrant/initialImport.sh

echo '#################### Populate SOLR index'
php $MEDIAWIKI/extensions/EnhancedRetrieval/maintenance/updateSOLR.php -v
