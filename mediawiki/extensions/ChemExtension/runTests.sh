#!/bin/sh

php /var/www/html/mediawiki/tests/phpunit/phpunit.php -v /var/www/html/mediawiki/extensions/ChemExtension/test/**/*.php
