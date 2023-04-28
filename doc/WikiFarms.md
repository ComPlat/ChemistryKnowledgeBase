# WikiFarms

The WikiFarms extension allows creating several wikis with separate databases but same wiki code.
Only the user-related tables are shared among the wikis to have one common user base.

In principle, the way how it works is to intercept a request when it reaches LocalSettings.php.
Depending on the URL path, the whole set of environment settings is replaced
(DB name, ScriptPath, SOLR core, Upload-Directory) .This happens in the script "WikiSwitch.php" which needs
to be included manually in LocalSettings.php.

Additionally, it is checked if the currently logged-in user may access the wiki. If not, access is denied.
This happens in the hook "onBeforePageDisplay" (cf. Setup script)

##Jobs
* *CreateWikiJob*: Creates a new wiki async. Parameters:
  * wikiId: The wiki ID (primary key from wiki_farm table)
  * name: arbitrary name
* *RemoveWikiJob*: Removes an existing wiki async
  * wikiId: The wiki ID (primary key from wiki_farm table)
  
The wiki jobs actually use Bash scripts to do their job. (cf. Bash scripts)

##Repository
The extension creates 2 new tables in the database. These tables are also shared among all wikis.

* *wiki_farm*: Contains all created wikis.
    * id: primary key (wikiId)
    * wiki_name: Arbitrary name of the wiki
    * fk_created_by: Foreign key to the user table of Mediawiki
    * wiki_status: Possible status values: IN_CREATION, CREATED, TO_BE_DELETED
    * created_at: Timestamp

IN_CREATION means that there is a wiki job to create it, but it has not been run so far. 
CREATED means it was successfully created.
TO_BE_DELETED means that there is a wiki job to remove it, but it has not been run so far.

* *wiki_farm_user*: Contains relation between wiki and users which are allowed to access it
    * id: primary key
    * fk_user_id: Foreign key to the user table of Mediawiki
    * fk_wiki_id: Foreign key to the wiki_farm table
    * status_enum: Only possible value at the moment: "USER"
    * created_at: Timestamp
    
The according repository class is *WikiRepository*

##Endpoints
Endpoints are self-explanatory. They allow creating/deleting of wikis and adding/removing of users who
are allowed to access it.

##Specialpages
There is one special page where wikis can be created/removed and users can be added/removed:
Special:SpecialCreateWiki

###Javascript
The special page uses 2 scripts to implement the GUI.
* wf.special.createwiki.js
* wf.special.createwiki.ajax.js

##Bash scripts
* createWiki.sh: Creates a wiki. It creates a folder for the wiki with an upload folder, a solr-core and a database copy.
At last, it imports the wiki-schema content. Parameters:
  * wikiId: Id of the wiki (primary key in wiki_farm table)
  * wikiName: arbitrary name
* removeWiki.sh: Removes a wiki. Deletes the folder, the solr-core and the database. Parameters:
  * wikiId: Id of the wiki (primary key in wiki_farm table)
* checkIfWikiExists.sh: Check if a particular wiki exists. Checks if the folder, the solr-core and the 
  database exists. Parameters:
  * wikiId: Id of the wiki (primary key in wiki_farm table)

##Maintenance scripts
* runJobsForAllWikis.php: runs the job-queue for all existing wikis
* runRefreshIndexForAllWikis.php: runs the SOLR refresh for all wikis
* runSetupForAllWikis.php: Runs the database setup for all wikis

The scripts work like following: They started by a shell script, and then they determine the existing wikis
and start for each wiki the shell script for this wiki. This shell script then starts the actual 
maintenance script for one wiki.

Example:

runJobsForAllWikis.sh -> runJobsForAllWikis.php -> runJobsForWiki.sh -> runJobs.php

The other scripts are just for local debugging and not used in production:
* addCreateWikiJob.php: creates a wiki
* addUser.php: adds a user to a wiki