<?php

namespace DIQA\WikiFarm;

use DIQA\WikiFarm\WikiGenerator\WikiGenerator;
use MediaWiki\MediaWikiServices;
use Exception;

class RemoveWikiJob extends \Job
{

    private WikiRepository $wikiRepository;

    public function __construct( $title, $params ) {
        parent::__construct( 'CreateWikiJob', $title, $params );
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $this->wikiRepository = new WikiRepository($dbr);
    }

    public function run()
    {
        $wikiId = $this->params['wikiId'];

        $wikiGenerator = new WikiGenerator();
        try {
            $wikiGenerator->rollback($wikiId);
            $this->wikiRepository->removeWiki($wikiId);
        } catch (Exception $e) {
            $this->wikiRepository->updateToFailed($wikiId);
        }
    }

}