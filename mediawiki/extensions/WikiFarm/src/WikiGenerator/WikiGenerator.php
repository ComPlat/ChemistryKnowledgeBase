<?php

namespace DIQA\WikiFarm\WikiGenerator;

use DIQA\WikiFarm\Exceptions\ValidationException;
use MediaWiki\MediaWikiServices;

class WikiGenerator {

    private array $wikiConfig;


    /**
     * @throws ValidationException
     */
    public function createNewWikiFromFile($jsonFile): void
    {
        $this->log("Reading configuration file '$jsonFile' ...");
        $this->wikiConfig = $this->readConfig($jsonFile);

        $this->createWiki();
    }

    /**
     * @throws ValidationException
     */
    public function createNewWikiFromConfig($jsonConfig): void {
        $this->log("Using configuration: " . print_r($jsonConfig, true));
        $this->wikiConfig = $this->getWikiConfig($jsonConfig);
        $this->createWiki();
    }

    /**
     * @throws ValidationException
     */
    private function readConfig($jsonFile): array {
        if (!file_exists($jsonFile) || !is_readable($jsonFile)) {
            throw new ValidationException("Cannot read configuration: '$jsonFile'");
        }
        $jsonConfig = json_decode(file_get_contents($jsonFile));
        if (is_null($jsonConfig)) {
            throw new ValidationException("Cannot parse configuration: '$jsonFile'");
        }

        return $this->getWikiConfig($jsonConfig);
    }

    private function getWikiConfig($jsonConfig): array {
        global $wgServer, $IP;
        $defaultEnvFolder = "$IP/extensions/WikiFarm/resources/env-farm-template";

        $wikiConfig = [];
        $wikiConfig['serverName']       = $jsonConfig->serverName ?? $wgServer;
        $wikiConfig['wikiId']           = $jsonConfig->wikiId;
        $wikiConfig['wikiName']         = $jsonConfig->wikiName;
        $wikiConfig['useLdap']          = $jsonConfig->useLdap ?? true;
        $wikiConfig['readForAll']       = $jsonConfig->readForAll ?? false;
        $wikiConfig['favicon']          = $jsonConfig->favicon ?? "$defaultEnvFolder/favicon.ico";
        $wikiConfig['logo']             = $jsonConfig->logo ?? "$defaultEnvFolder/logo.png";
        $wikiConfig['wordmark']         = $jsonConfig->wordmark ?? "$defaultEnvFolder/wordmark.png";;
        $wikiConfig['groupsForLogin']   = $jsonConfig->ldap->groupsForLogin ?? [];
        $wikiConfig['groupsForWriting'] = $jsonConfig->ldap->groupsForWriting ?? [];
        $wikiConfig['groupsForAdmin']   = $jsonConfig->ldap->groupsForAdmin ?? [];

        # add default DB credentials
        global $wgDBuser, $wgDBpassword;
        $wikiConfig['dbUser'] = $wgDBuser;
        $wikiConfig['dbPassword'] = $wgDBpassword;
        return $wikiConfig;
    }

    /**
     * @throws ValidationException
     */
    private function validatePrerequisites() {
        global $IP;

        $wikiID = $this->wikiConfig['wikiId'];
        if (preg_match("/^[a-z][a-z_0-9]*$/", $wikiID) !== 1) {
            throw new ValidationException("config.wikiId '$wikiID' must be alphanumeric with underscore and all lowercase");
        }
        if (file_exists("$IP/env-farm-$wikiID")) {
            throw new ValidationException("env-config with ID '$wikiID' already exists");
        }

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $result = $dbr->query("SHOW DATABASES LIKE '$wikiID'");
        if ($result->count()) {
            throw new ValidationException("DB with ID '$wikiID' already exists");
        }

        if ($this->doesSolrCoreExist($wikiID)) {
            throw new ValidationException("SOLR core with ID '$wikiID' already exists");
        }

        global $mysqlBin;
        if (!isset($mysqlBin) || $mysqlBin === '') {
            throw new ValidationException('$mysqlBin variable not set');
        }

        global $solrBin;
        if (!isset($solrBin) || $solrBin === '') {
            throw new ValidationException('$solrBin variable not set');
        }

        global $solrCoreTemplate;
        if (!isset($solrCoreTemplate) || $solrCoreTemplate === '') {
            throw new ValidationException('$solrCoreTemplate variable not set');
        }

        global $phpBin;
        if (!isset($phpBin) || $phpBin === '') {
            throw new ValidationException('$phpBin variable not set');
        }

        global $publicHtml;
        if ((!isset($publicHtml) || $publicHtml === '') && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            throw new ValidationException('$publicHtml variable not set');
        }

        if (!file_exists($this->wikiConfig['favicon']) || !is_readable($this->wikiConfig['favicon'])) {
            throw new ValidationException("'{$this->wikiConfig['favicon']}' does not exist or is not readable");
        }

        if (!file_exists($this->wikiConfig['logo']) || !is_readable($this->wikiConfig['logo'])) {
            throw new ValidationException("'{$this->wikiConfig['logo']}' does not exist or is not readable");
        }

        if (!file_exists($this->wikiConfig['wordmark']) || !is_readable($this->wikiConfig['wordmark'])) {
            throw new ValidationException("'{$this->wikiConfig['wordmark']}' does not exist or is not readable");
        }
    }

    /**
     * @throws ValidationException
     */
    private function doesSolrCoreExist(string $solrCore): bool
    {
        if (defined('ER_EXTENSION_VERSION')) {
            global $fsgSolrHost, $fsgSolrPort;
            $host = $fsgSolrHost ?? 'localhost';
            $port = $fsgSolrPort ?? '8983';
        } else if (defined('FS2_EXTENSION_VERSION')) {
            global $fs2gBackendConfig;
            $host = $fs2gBackendConfig['host'] ?? 'localhost';
            $port = $fs2gBackendConfig['port'] ?? '8983';
        } else {
            return false;
        }

        $jsonResponse = @file_get_contents("http://$host:$port/solr/admin/cores?action=STATUS&core=$solrCore");
        $response = json_decode($jsonResponse);
        if (is_null($response)) {
            throw new ValidationException("Cannot request SOLR at '$host:$port'. Maybe it is not running?");
        }
        return isset($response->status->{$solrCore}->name);
    }

    /**
     * @throws ValidationException
     */
    private function createEnvFolder() {

        global $IP;

        $wikiID = $this->wikiConfig['wikiId'];
        // 1. create folder and copy template file
        if (!mkdir("$IP/env-farm-$wikiID")) {
            throw new ValidationException("Could not create folder '$IP/env-farm-$wikiID'");
        }
        FileUtils::recurseCopy("$IP/extensions/WikiFarm/resources/env-farm-template", "$IP/env-farm-$wikiID");
        unlink("$IP/env-farm-$wikiID/env.php.template");

        // 2. replace variables in env.php
        $envContent = file_get_contents("$IP/extensions/WikiFarm/resources/env-farm-template");
        foreach($this->wikiConfig as $variable => $value) {
            if (is_array($value)) {
                $valueToStrings = array_map(fn($e) => '"'.$e.'"', $value);
                $envContent = str_replace('{{' . $variable . '}}', join(",\n", $valueToStrings), $envContent);
            } else if (is_bool($value)) {
                $envContent = str_replace('{{' . $variable . '}}', $value ? 'true' : 'false', $envContent);
            } else {
                $envContent = str_replace('{{' . $variable . '}}', $value, $envContent);
            }
        }
        file_put_contents("$IP/env-farm-$wikiID/env.php", $envContent);

        // 3. copy image files
        $this->checkAndCopyFileToEnv($this->wikiConfig['favicon'], $wikiID, 'favicon.ico');
        $this->checkAndCopyFileToEnv($this->wikiConfig['logo'], $wikiID, 'logo.png');
        $this->checkAndCopyFileToEnv($this->wikiConfig['wordmark'], $wikiID, 'wordmark.png');

    }

    /**
     * @throws ValidationException
     */
    private function checkAndCopyFileToEnv($filePath, $wikiID, $destFilename) {
        global $IP;
        if (!copy($filePath, "$IP/env-farm-$wikiID/$destFilename")) {
            throw new ValidationException("Could not copy file '$filePath'");
        }
    }

    /**
     * @throws ValidationException
     */
    private function createWikiDB() {
        global $mysqlBin, $wgDBuser, $wgDBpassword, $IP;

        $wikiID = $this->wikiConfig['wikiId'];
        global $wgWikiFarmDBPattern;
        if (isset($wgWikiFarmDBPattern)) {
            $dbName = preg_replace('/\{wiki}/', $wikiID, $wgWikiFarmDBPattern);
        } else {

            $dbName = $wikiID . "_wikidb";
        }
        $command = "\"$mysqlBin\" -u $wgDBuser -p$wgDBpassword -e \"CREATE DATABASE $dbName\"";
        $this->log( " - Creating database with command: $command");
        $ret = $this->runShellCommand($command);
        if ($ret !== 0) {
            throw new ValidationException("Database '$dbName' could not be created");
        }

        $command = "\"$mysqlBin\" -u $wgDBuser -p$wgDBpassword --database=$dbName < $IP/maintenance/tables-generated.sql";
        $this->log( " - Importing Wiki tables with command: $command");
        $ret = $this->runShellCommand($command);
        if ($ret !== 0) {
            throw new ValidationException("Database tables for '$dbName' could not be created");
        }
    }

    /**
     * @throws ValidationException
     */
    private function createSolrCore() {
        global $solrCoreTemplate;

        $wikiID = $this->wikiConfig['wikiId'];
        // 1. prepare SOLR conf folder
        $tmpFolder = wfTempDir() . '/solr_'.uniqid();
        if (!mkdir($tmpFolder)) {
            throw new ValidationException("$tmpFolder cannot be created");
        }
        if (!is_writable($tmpFolder)) {
            throw new ValidationException("$tmpFolder is not writeable");
        }
        if (!is_readable($solrCoreTemplate)) {
            throw new ValidationException("$solrCoreTemplate is not readable");
        }
        $this->log( " - Copying core to temp-folder: $tmpFolder...");
        FileUtils::recurseCopy($solrCoreTemplate, $tmpFolder);

        // 2. create SOLR core
        $command = $this->createSolrCoreCommand($wikiID, $tmpFolder);
        $ret = $this->runShellCommand($command);
        if ($ret !== 0) {
            throw new ValidationException("Creation of SOLR core '$wikiID' failed");
        }
    }

    private function createSolrCoreCommand($wikiID, $coreConf): string {
        global $solrBin;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return  "\"$solrBin\" create_core -c $wikiID -d $coreConf";
        } else {
            return "sudo -i -u solr bash -c \"$solrBin create_core -c $wikiID -d $coreConf\"";
        }
    }

    private function removeSolrCoreCommand($wikiID): string {
        global $solrBin;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return  "\"$solrBin\" delete -c $wikiID";
        } else {
            return "sudo -i -u solr bash -c \"$solrBin delete -c $wikiID\"";
        }
    }

    private function initializeDB()
    {
        global $IP, $phpBin;
        $wikiID = $this->wikiConfig['wikiId'];

        $wikiSchemaFolder = getenv('WIKISCHEMA') ? getenv('WIKISCHEMA') : "$IP/../wikischema";
        $wikiImageFolder = getenv('WIKIIMAGES') ? getenv('WIKIIMAGES') : "$IP/../wikiimages";

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // for Windows, we must not have quotes here
            $setWikiId = "SET WIKI=$wikiID";
        } else {
            $setWikiId = "export WIKI=\"$wikiID\"";
        }

        $ret = $this->runShellCommand("$setWikiId&& \"$phpBin\" $IP/extensions/SemanticMediaWiki/maintenance/setupStore.php 2>&1");
        $ret = $this->runShellCommand("$setWikiId&& \"$phpBin\" $IP/maintenance/update.php 2>&1");
        $ret = $this->runShellCommand("$setWikiId&& \"$phpBin\" $IP/extensions/PageImport/maintenance/WikiImport.php --directory=\"$wikiSchemaFolder\" 2>&1");
        $ret = $this->runShellCommand("$setWikiId&& \"$phpBin\" $IP/maintenance/importImages.php --search-recursively --overwrite \"$wikiImageFolder\" 2>&1");
    }

    private function populateSolrIndex() {
        global $IP, $phpBin;
        $wikiID = $this->wikiConfig['wikiId'];

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // for Windows, we must not have quotes here
            $setWikiId = "SET WIKI=$wikiID";
        } else {
            $setWikiId = "export WIKI=\"$wikiID\"";
        }

        if (defined('ER_EXTENSION_VERSION')) {
            $updateCommand = "$IP/extensions/EnhancedRetrieval/maintenance/updateSOLR.php";
        } else if (defined('FS2_EXTENSION_VERSION')) {
            $updateCommand = "$IP/extensions/FacetedSearch2/maintenance/updateSOLR.php";
        } else {
            return;
        }

        $this->runShellCommand("$setWikiId&& \"$phpBin\" $updateCommand -v 2>&1");
    }

    public function rollback($wikiID) {
        global $IP;

        $filename = "$IP/env-farm-$wikiID";
        $this->log( " - Removing env-folder $filename...");
        if (file_exists($filename)) {
            FileUtils::rrmdir($filename);
        }

        $dbName = $wikiID."_wikidb";
        $this->log( " - Removing database $dbName...");
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $dbr->query("DROP DATABASE IF EXISTS $dbName");

        $this->log( " - Removing SOLR core...");
        $command = $this->removeSolrCoreCommand($wikiID);
        $ret = $this->runShellCommand($command);
    }

    private function runShellCommand(string $command): int {
        $this->log( "   System command: $command");
        $line = system($command, $ret);
        $this->log( "   Result=$ret: $line");
        return $ret;
    }

    /**
     * @param string $msg
     * @return void
     */
    public function log(string $msg): void {
        echo("WikiGenerator:\t$msg\n");
    }

    /**
     * @throws ValidationException
     */
    private function createSymlink(): void
    {
        global $publicHtml, $wgWikiFarmEntryPoint;
        $wikiID = $this->wikiConfig['wikiId'];
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // on Windows not possible
        } else {

            $ret = $this->runShellCommand("sudo ln -s $wgWikiFarmEntryPoint $publicHtml/$wikiID");
            if ($ret !== 0) {
                throw new ValidationException("Creation of Symlink failed for '$wikiID' failed");
            }
        }
    }

    /**
     * @return void
     * @throws ValidationException
     */
    public function createWiki(): void
    {
        $this->log("Validating configuration parameters ...");
        $this->validatePrerequisites();

        $this->log("Creating folder for the new Wiki ...");
        $this->createEnvFolder();

        $this->log("Creating DB for the new Wiki ...");
        $this->createWikiDB();

        $this->log("Creating SOLR config and index for the new Wiki ...");
        $this->createSolrCore();

        $this->log("Initializing the content for the new Wiki ...");
        $this->initializeDB();

        $this->log("Populating SOLR index for the new Wiki ...");
        $this->populateSolrIndex();

        $this->log("Adding new Wiki to the WikiFarm ...");
        $this->createSymlink();
    }
}
