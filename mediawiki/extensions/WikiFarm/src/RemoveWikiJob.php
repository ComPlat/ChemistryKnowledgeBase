<?php

namespace DIQA\WikiFarm;

use DIQA\WikiFarm\WikiGenerator\WikiGenerator;
use MediaWiki\MediaWikiServices;
use Exception;

class RemoveWikiJob extends \Job
{
    private LoggerUtils $loggerUtils;
    private WikiRepository $wikiRepository;

    public function __construct( $title, $params ) {
        parent::__construct( 'RemoveWikiJob', $title, $params );
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $this->wikiRepository = new WikiRepository($dbr);
        $this->loggerUtils = new LoggerUtils('RemoveWikiJob', 'WikiFarm');
    }

    public function run()
    {
        $wikiId = $this->params['wikiId'];

        $wikiGenerator = new WikiGenerator();
        try {
            $wikiGenerator->rollback("wiki$wikiId");
            $this->wikiRepository->removeWiki($wikiId);
            $this->loggerUtils->log("Wiki $wikiId removed");
        } catch (Exception $e) {
            $this->loggerUtils->log("Failed to remove wiki $wikiId: " . $e->getMessage());
            $this->wikiRepository->updateToFailed($wikiId);
        }
    }

}