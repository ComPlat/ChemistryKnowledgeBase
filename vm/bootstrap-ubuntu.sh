#!/usr/bin/env bash

printf "\n\n\n#################### INSTALLATION\n"
date

# Repo
sudo add-apt-repository multiverse
sudo apt update

# Guest extensions
sudo apt -y install virtualbox-guest-dkms


printf "\n\n\n#################### Install apache\n"
date
sudo apt -y install apache2
sudo ufw allow "Apache Full"
sudo a2enmod rewrite
sudo sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf


printf "\n\n\n#################### Install MySQL/Maria\n"
date
sudo apt -y install mariadb-server
sudo service mysqld start
sudo mariadb -e "GRANT ALL ON *.* TO 'admin'@'localhost' IDENTIFIED BY 'vagrant' WITH GRANT OPTION;"
sudo mariadb -e "FLUSH PRIVILEGES;"


printf "\n\n\n#################### Install Node.js, npm and Less\n"
date
# https://linuxize.com/post/how-to-install-node-js-on-centos-7/
# this curl/bash seems not to be needed anymore?! curl -sL https://rpm.nodesource.com/setup_10.x | sudo bash -
sudo apt -y install nodejs npm
node --version
npm --version

# installing less
# see http://lesscss.org/
sudo npm install -g less
lessc --version


printf "\n\n\n#################### Install PHP\n"
date
sudo apt -y install php libapache2-mod-php php-mysql php-xml php-mbstring php-curl


printf "\n\n\n#################### Install composer\n"
date
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo apt -y install unzip


printf "\n\n\n#################### Get Powersuche\n"
date
sudo wget https://downloads.diqa-pm.com/free/power-search/2.3.0/powersearch-2.3.0.zip


printf "\n\n\n#################### Install ImageMagick\n"
date
sudo apt -y install imagemagick


printf "\n\n\n#################### Install Memcached\n"
date
sudo apt -y install memcached


printf "\n\n\n#################### Java\n"
date
sudo apt -y install default-jre

printf "\n\n\n#################### Tools\n"
sudo apt -y install dos2unix
sudo dos2unix /vagrant/*.sh


printf "\n\n\n#################### Configure Apache changes\n"
date
sudo sh -c "echo '127.0.0.1   chemwiki.localhost' >> /etc/hosts"
sudo sh -c "echo '127.0.0.1   chemwiki.local' >> /etc/hosts"


printf "\n\n\n#################### Start services\n"
sudo service apache2 start
sudo service memcached start
sudo service mysqld start


printf "\n\n\n#################### Show service status\n"
date
sudo service apache2 status
sudo service memcached status
sudo service mysqld status

printf "\n\n\n#################### Finished bootstrap.sh\n"
date
