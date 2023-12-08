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
use Title;

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

            $this->logger->log("ReplaceMoleculeJob with inchikey {$this->params['oldMoleculeKey']}");
            $this->logger->log(print_r($this->params, true));

            // process all pages which references to "targetChemFormId" in the index
            $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
            $repo = new ChemFormRepository($dbr);
            $pages = $repo->getPagesByChemFormId($this->params['oldChemFormId']);

            // sort so that subpages are processed before main pages
            usort($pages, function($p1, $p2) {
                if ($p1->isSubpage() && !$p2->isSubpage()) {
                    return -1;
                } else if (!$p1->isSubpage() && $p2->isSubpage()) {
                    return 1;
                } else return 0;
            });

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

                 // note: if there was no change, the index is only indirectly created because the molecule is used
                 // in an investigation of this page. In this case, add something to force a change.
                 if ($wikitext === $originalText) {
                     $wikitext .= "\nPlease remove this!";
                 }
                 $successful =  WikiTools::doEditContent($pageTitle, $wikitext, "auto-generated", EDIT_UPDATE);
                 if ($successful) {
                     $this->logger->log("Updated page: {$pageTitle->getPrefixedText()}");
                     $modificationLog->addModificationLogEntry($this->params['oldChemFormId'], $pageTitle, $replacedChemForm, $replacedChemFormLink,
                         !$replacedChemForm && !$replacedChemFormLink);
                 } else {
                     $this->logger->error("Update failed: {$pageTitle->getPrefixedText()}");
                 }
            }
            $modificationLog->saveLog();
            $this->logger->log("done.");

        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
