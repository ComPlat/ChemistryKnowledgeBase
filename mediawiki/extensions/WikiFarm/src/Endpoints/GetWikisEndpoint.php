<?php
namespace DIQA\WikiFarm\Endpoints;

use DIQA\WikiFarm\WikiRepository;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use Philo\Blade\Blade;

class GetWikisEndpoint extends SimpleHandler {

    public function run() {
        global $wgUser;

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new Blade ( $views, $cache );

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );
        $repository = new WikiRepository($dbr);

        global $wgServer;
        $allWikiCreated = $repository->getAllWikisCreatedById($wgUser->getId());
        $html = $blade->view ()->make ( "wiki-created-by",
            ['allWikiCreated' => $allWikiCreated,
                'baseURL' => $wgServer ]
        )->render ();

        return ['html' => $html];
    }

    public function needsWriteAccess() {
        return false;
    }

}