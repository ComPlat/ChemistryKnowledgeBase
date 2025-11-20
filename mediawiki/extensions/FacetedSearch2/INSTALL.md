# Faceted Search 2 
 
## Requirements

* PHP 8.3.x or higher
* Composer
* MediaWiki 1.43.x (LTS)
* SMW 6.x (optional)
* Apache SOLR server v8.30+

	You can setup your own Solr server. Please use the SOLR-schema given in:
		solr-config.zip (tested with Solr 8.30)
		
## Installation Instructions 

* Run in MW root folder:

      composer require diqa/faceted-search-2
 
* Add to your LocalSettings.php (adjust the values if different from default):

      global $fs2gSolrHost, $fs2gSolrPort; 
      global $fs2gSolrUser, $fs2gSolrPass, $fs2gSolrCore;

      # Default values:
      $fs2gSolrHost = 'localhost';
      $fs2gSolrPort = 8983;
      $fs2gSolrUser = '';
      $fs2gSolrPass = '';
      $fs2gSolrCore = 'mw';

      wfLoadExtension('FacetedSearch2');
      $smwgEnabledDeferredUpdate = false; // only if SMW installed

* Start the SOLR server 

* Create the initial index: 
    
      php {wiki-path}/extensions/FacetedSearch2/maintenance/updateSOLR.php -v
	    
      OR via composer:

	    cd {wiki-path}/extensions/FacetedSearch2
	    composer run-script update

* Goto page: Special:FacetedSearch2

That's it.

## CHANGE LOG

### v1.0
* Initial release

## Troubleshooting 

### Permission denied when accessing SOLR via the Wiki-Proxy 
If it yields something like this as error message (check browser log via F12), 
then you probably cannot make HTTP connections via Apache:
	
	"Failed to connect to ::1: Permission denied"
	
To fix it, run the following command on the shell:

	sudo setsebool httpd_can_network_connect 1