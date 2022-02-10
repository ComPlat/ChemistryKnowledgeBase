#!/usr/bin/env bash

# Repo
sudo apt update
sudo add-apt-repository multiverse

# Guest extensions
sudo apt -y install virtualbox-guest-dkms

# Apache
sudo apt -y install apache2
sudo ufw allow "Apache Full"
sudo a2enmod rewrite
sudo sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# MariaDB
sudo apt -y install mariadb-server
sudo mysql_secure_installation

# PHP
sudo apt -y install php libapache2-mod-php php-mysql php-xml php-mbstring php-curl

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Powersuche
sudo wget https://downloads.diqa-pm.com/free/power-search/2.3.0/powersearch-2.3.0.zip

# Tools
sudo apt -y install imagemagick
sudo apt -y install unzip
sudo apt -y install memcached

# Java
sudo apt install default-jre

echo '#################### Configure Apache changes'
sudo sh -c "echo '127.0.0.1   chemwiki.localhost' >> /etc/hosts"
sudo sh -c "echo '127.0.0.1   chemwiki.local' >> /etc/hosts"

echo '#################### Start services'
sudo service apache2 start
sudo service memcached start
sudo service mariadb start
