#!/usr/bin/env bash

if [ ! -e $MEDIAWIKI/maintenance/runJobs.php ]; then
    echo ERROR: Environment variable MEDIAWIKI must be properly set.
    echo ERROR: Check setup-XXX.sh for setting environment variables. 
    exit 1
fi

php $MEDIAWIKI/maintenance/createAndPromote.php                                                       --force 'User'            'root22root'
php $MEDIAWIKI/maintenance/createAndPromote.php                                                       --force 'DIQA'            'root22root'
php $MEDIAWIKI/maintenance/createAndPromote.php                                                       --force 'Angemeldeter'    'root22root'
php $MEDIAWIKI/maintenance/createAndPromote.php --sysop --bureaucrat --custom-groups=smwadministrator --force 'Administrator'   'root22root'
php $MEDIAWIKI/maintenance/createAndPromote.php --bureaucrat                                          --force 'Bureaucrat'      'root22root'

php $MEDIAWIKI/maintenance/createAndPromote.php --sysop --bureaucrat --custom-groups=smwadministrator --force 'WikiSysop'       'root22root'
php $MEDIAWIKI/maintenance/createAndPromote.php --sysop                                               --force 'WikiExport'      'root22root'
