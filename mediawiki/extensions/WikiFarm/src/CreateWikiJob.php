<?php
namespace DIQA\WikiFarm;

use MediaWiki\MediaWikiServices;
use Exception;

class CreateWikiJob extends \Job {

    private $dbr;
    private $wikiRepository;

    public function __construct( $title, $params ) {
        parent::__construct( 'CreateWikiJob', $title, $params );
        $this->dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );
        $this->wikiRepository = new WikiRepository($this->dbr);
    }

    public function run()
    {
        global $IP;
        $wikiId = $this->params['wikiId'];
        $name = escapeshellarg($this->params['name']);
        try {
            $this->checkPreconditions($wikiId);
            shell_exec("$IP/extensions/WikiFarm/bin/createWiki.sh wiki$wikiId $name");
            $this->wikiRepository->updateToCreated($wikiId);
        } catch(Exception $e) {
            wfDebugLog('CreateWikiJob', $e->getMessage());
            $this->wikiRepository->removeWiki($wikiId);
        }
    }

    /**
     * @throws Exception
     */
    private function checkPreconditions($wikiId) {

        $res = $this->dbr->select('INFORMATION_SCHEMA.SCHEMATA', ['SCHEMA_NAME'], ['SCHEMA_NAME' => "chemwiki$wikiId"]);
        if ($res->numRows() > 0) {
            throw new Exception("Database 'chemwiki$wikiId' already exists.");
        }

    }
}