#!/usr/bin/env bash

export MEDIAWIKI=/var/www/html/mediawiki

# Install Powersearch
sudo mkdir /opt/powersearch
sudo mv /home/vagrant/powersearch-2.3.0.zip /opt/powersearch
cd /opt/powersearch
sudo unzip powersearch-2.3.0.zip
sudo unzip solr-8.3.0.zip

sudo chmod +x install_solr_service.sh
sudo ./install_solr_service.sh solr-8.3.0.zip

sudo cp -r mw /var/solr/data
sudo chown -R solr:solr /var/solr/data/mw
sudo service solr restart

#################### Drop DB -- if desired
# Be very careful. This step cannot be undone.
# If you want to completely remove all contents and all users etc. from
# your Wiki-DB uncomment the next line before running this script.
#mysql -u root -pvagrant -e "DROP DATABASE IF EXISTS chemwiki;"
mysql -u root -pvagrant -e "CREATE DATABASE IF NOT EXISTS chemwiki;"
mysql -u root -pvagrant --database=chemwiki < /var/www/html/mediawiki/database.sql

# Create wikis
#sudo sh /vagrant/createWiki.sh main Hauptwiki
#sudo sh /vagrant/createWiki.sh wiki2 Wiki2



