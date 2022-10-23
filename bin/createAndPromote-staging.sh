#!/usr/bin/env bash

if [ ! -e $MEDIAWIKI/maintenance/runJobs.php ]; then
    echo ERROR: Environment variable MEDIAWIKI must be properly set.
    echo ERROR: Check setup-XXX.sh for setting environment variables. 
    exit 1
fi

php $MEDIAWIKI/maintenance/createAndPromote.php                                 --force 'User'            'rootroot'
php $MEDIAWIKI/maintenance/createAndPromote.php                                 --force 'DIQA'            'rootroot'
php $MEDIAWIKI/maintenance/createAndPromote.php                                 --force 'Angemeldeter'    'WeC5W7GWhjDPSDs9USmR'
php $MEDIAWIKI/maintenance/createAndPromote.php --sysop --bureaucrat            --force 'Administrator'   '3fJGiVyMGQrZmlDHFnX0'
php $MEDIAWIKI/maintenance/createAndPromote.php --bureaucrat                    --force 'Bureaucrat'      'ulSoS99Nco4RoxxmV2fr'

php $MEDIAWIKI/maintenance/createAndPromote.php --sysop --bureaucrat            --force 'WikiSysop'       'rootroot'
php $MEDIAWIKI/maintenance/createAndPromote.php --sysop                         --force 'WikiExport'      'rootroot'
