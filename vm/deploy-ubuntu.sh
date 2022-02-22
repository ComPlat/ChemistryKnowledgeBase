#!/usr/bin/env bash

export MEDIAWIKI=/var/www/html/mediawiki

# this must be aligned with $wgDBname from env.php or LocalSettings
export WIKIDB=chemwiki
export DB_USER=admin
export DB_PWD=vagrant

printf "\n\n\n#################### Install Powersearch\n"
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
#mysql -u $DB_USER -p$DB_PWD -e "DROP DATABASE IF EXISTS $WIKIDB;"
#printf "\n\n\n#################### Initialize MySQL-DB\n"
#date
#sudo mysqladmin -u $DB_USER password $DB_PWD
mysql -u $DB_USER -p$DB_PWD -e "CREATE DATABASE IF NOT EXISTS $WIKIDB;"
#mysql -u $DB_USER -p$DB_PWD -e "GRANT ALL PRIVILEGES ON $WIKIDB.* TO '$DB_USER'@'%' IDENTIFIED BY '$DB_PWD';"
printf "\n\n\n#################### Initialize MediaWiki\n"
date

# store our LocalSettings in a save place (because install.php wants to create a new one)
mv $MEDIAWIKI/LocalSettings.php $MEDIAWIKI/LocalSettings.orig

php $MEDIAWIKI/maintenance/install.php \
  --dbserver localhost --dbname $WIKIDB --dbuser $DB_USER --dbpass $DB_PWD \
  --lang en --pass root22root --scriptpath /mediawiki \
  --server http://chemwiki.local \
  ChemWiki WikiSysop

# restore our LocalSettings (since install.php created a new one)
mv $MEDIAWIKI/LocalSettings.php $MEDIAWIKI/LocalSettings.auto
mv $MEDIAWIKI/LocalSettings.orig $MEDIAWIKI/LocalSettings.php


printf "\n\n\n#################### Update MediaWiki\n"
date
php $MEDIAWIKI/maintenance/update.php


printf "\n\n\n#################### Create additional users\n"
date
~/bin/createAndPromote-local.sh


printf "\n\n\n#################### Initial Import\n"
date
source /vagrant/initialImport.sh


# For WikiFarm only
#sudo $MEDIAWIKI/extensions/WikiFarm/bin/createWiki.sh main Hauptwiki
## Create wikis
#sudo sh /vagrant/createWiki.sh main Hauptwiki
#sudo sh /vagrant/createWiki.sh wiki2 Wiki2


printf "\n\n\n#################### Finished deploy.sh\n"
date
