<?php

namespace DIQA\WikiFarm;

use MediaWiki\MediaWikiServices;
use Exception;

class RemoveWikiJob extends \Job
{

    private $dbr;
    private $wikiRepository;

    public function __construct($title, $params)
    {
        parent::__construct('RemoveWikiJob', $title, $params);
        $this->dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_PRIMARY
        );
        $this->wikiRepository = new WikiRepository($this->dbr);
    }

    public function run()
    {
        global $IP;
        $wikiId = $this->params['wikiId'];


        try {
            if ($this->isWikiCompletelyRemoved($wikiId)) {
                $this->wikiRepository->removeWiki($wikiId);
                return;
            }
            echo shell_exec("bash $IP/extensions/WikiFarm/bin/removeWiki.sh wiki$wikiId 2>&1");
            if ($this->isWikiCompletelyRemoved($wikiId)) {
                $this->wikiRepository->removeWiki($wikiId);
            } else {
                wfDebugLog('RemoveWikiJob', "Wiki $wikiId could not be removed completely");
            }
        } catch (Exception $e) {
            wfDebugLog('RemoveWikiJob', $e->getMessage());
        }
    }

    private function isWikiCompletelyRemoved($wikiId): bool
    {
        global $IP;
        $resultCheck = shell_exec("bash $IP/extensions/WikiFarm/bin/checkIfWikiExists.sh wiki$wikiId 2> /dev/null");
        return (strpos($resultCheck, "wiki") === false
            && strpos($resultCheck, "db") === false
            && strpos($resultCheck, "solr") === false
        );
    }

}