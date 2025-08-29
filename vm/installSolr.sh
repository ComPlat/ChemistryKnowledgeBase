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