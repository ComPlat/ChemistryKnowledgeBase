#!/usr/bin/env bash

echo '#################### INSTALLATION'
sudo yum update --assumeyes 
sudo yum install yum-utils --assumeyes
sudo yum install https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm --assumeyes
sudo yum install http://rpms.remirepo.net/enterprise/remi-release-7.rpm --assumeyes


echo '#################### Install apache'
sudo yum install httpd --assumeyes


# echo '#################### Install SSL'
# sudo yum install mod_ssl openssl --assumeyes


echo '#################### Install WGET'
sudo yum install wget --assumeyes


echo '#################### Install HTOP'
sudo yum install htop --assumeyes


echo '#################### Install MySQL/Maria'
sudo yum install mariadb-server mariadb --assumeyes
sudo systemctl start mariadb.service
sudo systemctl enable mariadb.service


echo '#################### Install PHP'
sudo yum-config-manager --enable remi-php73 --assumeyes
sudo yum install php php-mcrypt php-cli php-gd php-curl php-mysql php-ldap php-zip php-fileinfo --assumeyes
sudo yum install php-xml.x86_64 --assumeyes
sudo yum install php-mbstring.x86_64 --assumeyes
sudo yum install php-soap --assumeyes
sudo yum install php-intl.x86_64 --assumeyes


echo '#################### Install Memcached'
sudo yum install memcached.x86_64 --assumeyes


echo '#################### Install git'
sudo yum install git.x86_64 --assumeyes


echo '#################### Install composer'
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer


echo '#################### Install Nano'
sudo yum install nano --assumeyes


echo '#################### Install Chrome'
sudo cp /vagrant/google-chrome.repo /etc/yum.repos.d/google-chrome.repo
sudo yum install google-chrome-stable --assumeyes


echo '#################### Install wkhtmltopdf'
sudo yum install xorg-x11-fonts-75dpi  --assumeyes

wget https://downloads.wkhtmltopdf.org/0.12/0.12.4/wkhtmltox-0.12.4_linux-generic-amd64.tar.xz
tar vxf wkhtmltox-0.12.4_linux-generic-amd64.tar.xz wkhtmltox
sudo rsync -vlr wkhtmltox/* /usr/local/
sudo ln -s /usr/local/bin/wkhtmltopdf /usr/bin/wkhtmltopdf
sudo ln -s /usr/local/bin/wkhtmltoimage /usr/bin/wkhtmltoimage
wkhtmltopdf -V
rm wkhtmltox-0.12.4_linux-generic-amd64.tar.xz
rm -rf wkhtmltox/

sudo yum install cabextract --assumeyes
wget http://li.nux.ro/download/nux/dextop/el7/x86_64/msttcore-fonts-installer-2.6-1.noarch.rpm
sudo rpm -Uvh msttcore-fonts-installer-2.6-1.noarch.rpm


echo '#################### Install pdftk'
sudo wget http://li.nux.ro/download/nux/dextop/el7/x86_64/pdftk-2.02-1.el7.nux.i686.rpm
sudo yum install /lib/ld-linux.so.2 --assumeyes
sudo yum install libstdc++.i686 --assumeyes
sudo yum install zlib.i686 --assumeyes
sudo rpm -Uvh pdftk-2.02-1.el7.nux.i686.rpm

echo '#################### Download Power search'
sudo wget https://downloads.diqa-pm.com/free/power-search/2.3.0/powersearch-2.3.0.zip


echo '#################### Install ImageMagick'
sudo yum install ImageMagick --assumeyes

echo '#################### Install unzip'
sudo yum install unzip --assumeyes

echo '#################### Install Java8'
sudo yum install java-1.8.0-openjdk --assumeyes

echo '#################### Install tools required for Guest additions'
sudo yum install perl gcc dkms kernel-devel kernel-headers make bzip2 --assumeyes


echo '#################### Cleanup yum caches'
sudo yum clean all


echo '#################### Configure Apache changes'
sudo sh -c "echo '127.0.0.1   chemwiki.localhost' >> /etc/hosts"
sudo sh -c "echo '127.0.0.1   chemwiki.local' >> /etc/hosts"


echo '#################### Turn off firewall'
sudo systemctl disable firewalld


echo '#################### Make services start after reboot'
sudo chkconfig --levels 235 httpd on
sudo chkconfig --levels 235 memcached on
sudo chkconfig --levels 235 mariadb on
sudo chkconfig --levels 235 tomcat on


echo '#################### Start services'
sudo service httpd start
sudo service memcached start
sudo service mariadb start


echo '#################### Show service status'
sudo service httpd status
sudo service memcached status
sudo service mariadb status
mysqladmin status
