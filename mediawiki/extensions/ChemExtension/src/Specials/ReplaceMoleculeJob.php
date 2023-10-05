<?php

namespace DIQA\ChemExtension\Specials;

use DIQA\ChemExtension\Pages\ChemFormParser;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\ParserFunctions\ParserFunctionParser;
use DIQA\ChemExtension\Utils\ModifyMoleculeLog;
use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use MediaWiki\MediaWikiServices;
use Job;
use Exception;

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
        try {

            $this->logger->log("Replacing molecule with inchikey {$this->params['oldMoleculeKey']} by " . $this->params['chemform']);

            $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER);
            $repo = new ChemFormRepository($dbr);
            $chemFormId = $repo->getChemFormId($this->params['moleculeKey']);
            $pages = $repo->getPagesByChemFormId($chemFormId);

            $modificationLog = new ModifyMoleculeLog();
            foreach ($pages as $pageTitle) {
                $wikitext = WikiTools::getText($pageTitle);
                $chemFormParser = new ChemFormParser();
                $wikiTextAfterChemformChange = $chemFormParser->replaceChemForm($this->params['oldMoleculeKey'],
                    $wikitext, $this->params['chemform']);
                $replacedChemForm = ($wikiTextAfterChemformChange !== $wikitext);
                $parserFunctionParser = new ParserFunctionParser();
                $wikitextAfterLinkChange = $parserFunctionParser->replaceFunction($wikiTextAfterChemformChange,
                    'moleculelink', 'link', $this->params['oldMoleculeKey'],
                    ['link' => $this->params['chemform']->getMoleculeKey()]);
                $replacedChemFormLink = ($wikitextAfterLinkChange !== $wikiTextAfterChemformChange);
                if ($wikitextAfterLinkChange !== $wikitext) {
                    WikiTools::doEditContent($pageTitle, $wikiTextAfterChemformChange, "auto-generated", EDIT_UPDATE);
                    $this->logger->log("Updated page: {$pageTitle->getPrefixedText()}");
                }
                $modificationLog->addModificationLogEntry($chemFormId, $pageTitle, $replacedChemForm, $replacedChemFormLink,
                    !$replacedChemForm && !$replacedChemFormLink);
            }
            $modificationLog->saveLog();
            $this->logger->log("done.");

        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
