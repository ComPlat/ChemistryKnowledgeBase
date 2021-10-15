<?php
namespace DIQA\WikiFarm\Special;

class SpecialCreateWiki extends \SpecialPage {
    function __construct() {
        parent::__construct( 'SpecialCreateWiki' );
    }

    function execute( $par ) {
        $request = $this->getRequest();
        $output = $this->getOutput();
        $this->setHeaders();

        # Get request data from, e.g.
        $param = $request->getText( 'param' );

        # Do stuff
        # ...
        $wikitext = 'Hello world!';
        $html = '<button id="create-wiki">Create wiki</button>';
        $output->addHTML($html);
        //$output->addWikiTextAsInterface( $wikitext );
    }
}