# faceted-search-2
Next generation of EnhancedRetrieval

# Create VM

* Install Virtualbox - `https://www.virtualbox.org/`
* Install Vagrant - `https://www.vagrantup.com/`

Open cmd and change to folder "vm-ubuntu" and run:

    vagrant up

Enter VM by running:

    vagrant ssh

### SOLR

and go to `/var/www/html/mediawiki/vm-ubuntu` and run:

    sudo ./solr-installPowerSearch.sh
    sudo ./solr-installCore.sh

### ElasticSearch

goto to `/var/www/html/mediawiki/vm-ubuntu` and run:

    sudo ./install-elastic.sh
    sudo ./set-password-for-elastic.sh

This creates a user "elastic" with a password and sets it in the env.php file.

# Build Frontend

Install NPM - https://nodejs.org/en/download

Open CMD console and change to folder "fs-react"
* ``npm install`` (initially or after dependency update)
* ``npm start`` (to run the webserver for local development)
* ``npm run dev-build`` (DEV build)
* ``npm run build`` (PROD build)
* ``npm test`` (to run all unit tests)
* ``npm run it-test`` (to run all integration-tests)

# Backend 

To run backend tests, login via SSH to VM and run:

    /var/www/html/mediawiki/runTests.sh

To import test data for frontend:

    php /var/www/html/mediawiki/test/importTestdataForFrontend.php