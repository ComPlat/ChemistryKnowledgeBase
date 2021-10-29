<?php
namespace DIQA\WikiFarm\Special;

use DIQA\WikiFarm\WikiRepository;
use MediaWiki\MediaWikiServices;
use Philo\Blade\Blade;

class SpecialCreateWiki extends \SpecialPage {

    private $repository;
    private $blade;

    function __construct() {
        parent::__construct( 'SpecialCreateWiki' );

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new Blade ( $views, $cache );

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );
        $this->repository = new WikiRepository($dbr);
    }

    function execute( $par ) {
        global $wgUser;

        $request = $this->getRequest();
        $output = $this->getOutput();
        $this->setHeaders();

        # Get request data from, e.g.
        $param = $request->getText( 'param' );

        # Do stuff
        # ...
        $wikitext = 'Hello world!';
        $html = '<button id="create-wiki">Create wiki</button>';

        //$output->addWikiTextAsInterface( $wikitext );
        $allWikiCreated = $this->repository->getAllWikisCreatedById($wgUser->getId());
        $html .= $this->blade->view ()->make ( "wiki-created-by",
            ['allWikiCreated' => $allWikiCreated]
        )->render ();
        $output->addHTML($html);
    }
}