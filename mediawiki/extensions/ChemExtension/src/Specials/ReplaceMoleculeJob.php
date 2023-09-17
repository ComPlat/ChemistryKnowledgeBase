<?php

namespace DIQA\ChemExtension\Specials;

use DIQA\ChemExtension\Pages\ChemFormParser;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\ParserFunctions\ParserFunctionParser;
use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use MediaWiki\MediaWikiServices;
use Job;

class ReplaceMoleculeJob extends Job
{
    private $logger;


    public function __construct($title, $params)
    {
        parent::__construct('ReplaceMoleculeJob', $title, $params);
        $this->logger = new LoggerUtils('ReplaceMoleculeJob', 'ChemExtension');


    }

    public function run()
    {
        $this->logger->log("Replacing molecule with inchikey {$this->params['oldMoleculeKey']} by " . $this->params['chemform']);

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER);
        $repo = new ChemFormRepository($dbr);
        $chemFormId = $repo->getChemFormId($this->params['moleculeKey']);
        $pages = $repo->getPagesByChemFormId($chemFormId);

        foreach($pages as $pageTitle) {
            $wikitext = WikiTools::getText($pageTitle);
            $chemFormParser = new ChemFormParser();
            $newWikitext = $chemFormParser->replaceChemForm($this->params['oldMoleculeKey'], $wikitext, $this->params['chemform']);
            $parserFunctionParser = new ParserFunctionParser();
            $newWikitext = $parserFunctionParser->replaceFunction($newWikitext, 'moleculelink', 'link', $this->params['oldMoleculeKey'],
                ['link' => $this->params['chemform']->getMoleculeKey()]);
            if ($newWikitext !== $wikitext) {
                WikiTools::doEditContent($pageTitle, $newWikitext, "auto-generated", EDIT_UPDATE);
                $this->logger->log("Updated page: {$pageTitle->getPrefixedText()}");
            }
        }
        $this->logger->log("done.");
    }
}
