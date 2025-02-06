# WikiFarms

The WikiFarms extension allows creating several wikis with separate databases but same wiki code.
Only the user-related tables are shared among the wikis to have one common user base.

In principle, the way how it works is to intercept a request when it reaches LocalSettings.php.
Depending on the URL path, the whole set of environment settings is replaced
(DB name, ScriptPath, SOLR core, Upload-Directory) .This happens in the script "WikiSwitch.php" which needs
to be included manually in LocalSettings.php.

Additionally, it is checked if the currently logged-in user may access the wiki. If not, access is denied.
This happens in the hook "onBeforePageDisplay" (cf. Setup script)

* Licensed under GPL-v2
* Created by KIT and DIQA-Projektmanagement GmbH

