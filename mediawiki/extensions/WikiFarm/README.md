In order to activate WikiFarm:

1. Run 

   sudo $MEDIAWIKI/extensions/WikiFarm/bin/createWiki.sh main Hauptwiki

2. Uncomment in LocalSettings.php

   require_once ("extensions/WikiFarm/WikiSwitch.php");

   
Main wiki will be at:

http://localhost/main/mediawiki/