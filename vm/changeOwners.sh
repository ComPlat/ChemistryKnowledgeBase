#!/usr/bin/env bash

## required if rsync update
chown -R www-data:www-data /var/www/html/mediawiki/images
chmod 777 /var/www/html/mediawiki/extensions/ChemExtension/logFiles
chmod 777 /var/www/html/mediawiki/extensions/WikiFarm/logFiles