<?php
namespace DIQA\WikiFarm;

use MediaWiki\MediaWikiServices;
use Exception;

class RemoveWikiJob extends \Job {

    private $dbr;
    private $wikiRepository;

    public function __construct( $title, $params ) {
        parent::__construct( 'RemoveWikiJob', $title, $params );
        $this->dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );
        $this->wikiRepository = new WikiRepository($this->dbr);
    }

    public function run()
    {
        global $IP;
        $wikiId = $this->params['wikiId'];

        try {
            echo shell_exec("$IP/extensions/WikiFarm/bin/removeWiki.sh wiki$wikiId");
        } catch(Exception $e) {
            wfDebugLog('RemoveWikiJob', $e->getMessage());
        }
    }

}