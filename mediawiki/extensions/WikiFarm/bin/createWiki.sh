#!/usr/bin/env bash

RET=$(sudo id -u)
if [ $RET -ne 0 ]
  then echo "Can not run sudo"
  exit
fi

# Param1: wikiId
# Param2: wiki name

if [ -z "$1" ]
  then
    echo "No wikiId supplied"
    exit
fi

if [ -z "$2" ]
  then
    echo "No Wiki name supplied"
    exit
fi

export BASE=/var/www/html
export MEDIAWIKI=/var/www/html/mediawiki

echo 'Create www folder...'
sudo mkdir $BASE/$1
sudo mkdir $BASE/$1/images
sudo chmod 777 $BASE/$1/images
sudo ln -s $MEDIAWIKI $BASE/$1
sudo sh -c "echo '{\"name\":\"$2\"}' >> $BASE/$1/$1.json"
sudo sh -c "echo '' >> $BASE/$1/creation.log"
sudo chmod o+w $BASE/$1/creation.log
echo 'done.'

echo 'Create SOLR core...'
sudo su solr -c "/opt/solr/bin/solr create_core -c $1 -d $MEDIAWIKI/extensions/WikiFarm/bin/solrcore/conf" >> $BASE/$1/creation.log
echo 'done.'

echo 'Create database...'
export DB_USER=admin
export DB_PASS=vagrant
export DB_USER_ADMIN=admin
export DB_PASS_ADMIN=vagrant
if [ -e $MEDIAWIKI/env.sh ]
then
    source $MEDIAWIKI/env.sh
fi


mysql -u $DB_USER_ADMIN -p$DB_PASS_ADMIN -e "CREATE DATABASE chem$1;" >> $BASE/$1/creation.log
mysql -u $DB_USER_ADMIN -p$DB_PASS_ADMIN -e "GRANT ALL PRIVILEGES ON chem$1.* TO '$DB_USER'@'%' IDENTIFIED BY '$DB_PASS';" >> $BASE/$1/creation.log

mysql -u $DB_USER_ADMIN -p$DB_PASS_ADMIN --database=chem$1 < $MEDIAWIKI/database.sql
export REQUEST_URI="/$1/mediawiki"
php $MEDIAWIKI/maintenance/update.php --quick >> $BASE/$1/creation.log
echo 'done.'

echo '#################### Import wiki schema'
bash /vagrant/initialImport.sh >> $BASE/$1/creation.log

