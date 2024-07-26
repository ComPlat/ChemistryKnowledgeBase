#!/bin/bash

php /var/www/html/main/mediawiki/maintenance/install.php \
  --server "$MW_SERVER" \
  --scriptpath="$MW_SCRIPT_PATH" \
  --dbtype "mysql" \
  --dbname "chemmain" \
  --dbserver "host.docker.internal" \
  --dbpass "$MW_DBPASS" \
  --dbuser "$MW_DBUSER" \
  --lang "$MW_LANG" \
  --pass "$MW_PASS" \
  "$MW_SITENAME" "$MW_USER"

cp /var/www/html/main/mediawiki/LocalSettings.php.bak /var/www/html/main/mediawiki/LocalSettings.php
php /var/www/html/main/mediawiki/maintenance/update.php