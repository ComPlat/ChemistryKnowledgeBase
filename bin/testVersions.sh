##!/usr/bin/env bash

rpm -q centos-release

echo
echo "## CURL #################################################"
curl --version

echo
echo "## MYSQL ################################################"
mysql --version

echo
echo "## JAVA #################################################"
java -version

echo
echo "## PHP ##################################################"
php --version

echo
echo "## APACHE ###############################################"
httpd -v

echo
echo "## TOMCAT ###############################################"
tomcat version

echo
echo "## MEMCACHE #############################################"
memcached -h | head -n 1

echo
echo "## GIT ##################################################"
git --version

echo
echo "## PDFTK ################################################"
pdftk --version

echo
echo "## WKHTML ###############################################"
wkhtmltopdf -V

echo
echo "## CONVERT ##############################################"
convert --version

echo
echo "## FONTS ################################################" 
fc-list

echo
echo "## PHPINFO ##############################################" 
php $BIN/info.php
