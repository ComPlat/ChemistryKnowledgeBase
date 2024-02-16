<?php

namespace DIQA\ChemExtension\Jobs;

use DIQA\ChemExtension\Pages\ChemFormParser;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\ParserFunctions\ParserFunctionParser;
use DIQA\ChemExtension\Utils\ModifyMoleculeLog;
use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use MediaWiki\MediaWikiServices;
use Job;
use Exception;
use Title;

class AdjustMoleculeReferencesJob extends Job
{
    private $logger;


    public function __construct($title, $params)
    {
        parent::__construct('AdjustMoleculeReferencesJob', $title, $params);
        $this->logger = new LoggerUtils('AdjustMoleculeReferencesJob', 'ChemExtension');


    }

    public function run()
    {
        try {

            $this->logger->log("AdjustMoleculeReferencesJob with inchikey {$this->params['oldMoleculeKey']}");
            $this->logger->log(print_r($this->params, true));

            // process all pages which references to "oldChemFormId" in the index
            $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
            $repo = new ChemFormRepository($dbr);
            $pages = $repo->getPagesByChemFormId($this->params['oldChemFormId']);

            // sort so that subpages are processed before main pages
            WikiTools::sortPageListBySubpages($pages);

            $modificationLog = new ModifyMoleculeLog();
            foreach ($pages as $pageTitle) {
                $wikitext = WikiTools::getText($pageTitle);

                // replace chemform-tags
                $originalText = $wikitext;
                $chemFormParser = new ChemFormParser();
                $wikitext = $chemFormParser->replaceChemForm($this->params['oldMoleculeKey'],
                    $wikitext, $this->params['targetChemForm']);
                $replacedChemForm = ($wikitext !== $originalText);

                // replace molecule links
                $originalText = $wikitext;
                $parserFunctionParser = new ParserFunctionParser();
                $wikitext = $parserFunctionParser->replaceFunction($wikitext,
                    'moleculelink', 'link', $this->params['oldMoleculeKey'],
                    ['link' => $this->params['targetChemForm']->getMoleculeKey()]);
                $replacedChemFormLink = ($wikitext !== $originalText);

                // replace chemformIds. (if necessary)
                 if ($this->params['replaceChemFormId'] ?? false) {
                    $search = Title::newFromText($this->params['oldChemFormId'], NS_MOLECULE)->getPrefixedText();
                    $replace = Title::newFromText($this->params['targetChemFormId'], NS_MOLECULE)->getPrefixedText();
                    $wikitext = str_replace($search, $replace, $wikitext);
                }

                 if (($wikitext !== $originalText)) {
                     $successful = WikiTools::doEditContent($pageTitle, $wikitext, "auto-generated",
                         EDIT_UPDATE | EDIT_MINOR | EDIT_SUPPRESS_RC | EDIT_FORCE_BOT, null, true);
                     if ($successful) {
                         $this->logger->log("Updated page: {$pageTitle->getPrefixedText()}");
                         $modificationLog->addModificationLogEntry($this->params['oldChemFormId'], $pageTitle, $replacedChemForm, $replacedChemFormLink,
                             !$replacedChemForm && !$replacedChemFormLink);
                     } else {
                         $this->logger->error("Update failed: {$pageTitle->getPrefixedText()}");
                     }
                 }
            }
            $modificationLog->saveLog();
            $this->logger->log("done.");

        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
