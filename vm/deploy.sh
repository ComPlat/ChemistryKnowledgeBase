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

echo '#################### Initialize MySQL-DB'
mysqladmin -u root password vagrant

#################### Drop DB -- if desired
# Be very careful. This step cannot be undone.
# If you want to completely remove all contents and all users etc. from 
# your Wiki-DB uncomment the next line before running this script.
#mysql -u root -pvagrant -e "DROP DATABASE IF EXISTS chemwiki;"

# Create wikis
sudo dos2unix /var/www/html/mediawiki/extensions/WikiFarm/bin/createWiki.sh
sudo chmod +x /var/www/html/mediawiki/extensions/WikiFarm/bin/createWiki.sh
sudo sh /var/www/html/mediawiki/extensions/WikiFarm/bin/createWiki.sh main Hauptwiki
#sudo sh /vagrant/createWiki.sh wiki2 Wiki2

php /var/www/html/main/mediawiki/maintenance/runJobs.php
php /var/www/html/main/mediawiki/extensions/EnhancedRetrieval/maintenance/updateSOLR.php -v

