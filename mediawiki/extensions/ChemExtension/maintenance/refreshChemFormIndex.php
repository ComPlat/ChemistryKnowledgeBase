<?php

use DIQA\ChemExtension\Utils\WikiTools;
use MediaWiki\MediaWikiServices;

require_once __DIR__ . '/PageIterator.php';

class refreshChemFormIndex extends PageIterator
{

    public function __construct()
    {
        parent::__construct();
       $this->addOption('onlyexplink', 'Update only pages with #experimentlink parser function', false, false);
    }

    protected function init()
    {
        // when indexing everything, dependent pages do not need special treatment
        global $fsUpdateOnlyCurrentArticle;
        $fsUpdateOnlyCurrentArticle = true;
    }


    /**
     * Refreshes the chemform index for a title
     */
    protected function processPage(Title $title)
    {

        if (WikiTools::checkIfInTopicCategory($title)) {
            $text = WikiTools::getText($title);
            if ($this->hasOption("onlyexplink") && strpos($text, '#experimentlink:') === false) {
                return;
            }
            $services = MediaWikiServices::getInstance();
            $user = $services->getUserFactory()->newFromName("WikiSysop");
            $parser = clone MediaWikiServices::getInstance()->getParser();
            $parser->parse($text, $title, new ParserOptions($user));
            $mcs = new \DIQA\ChemExtension\MultiContentSave();
            $mcs->parseContentAndUpdateIndex($text, $title, false);
            print "\nrefresh:\t" . $title->getPrefixedText();
        }
    }

}

$maintClass = "refreshChemFormIndex";
require_once RUN_MAINTENANCE_IF_MAIN;
