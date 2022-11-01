<?php

namespace DIQA\ChemExtension;

use DIQA\ChemExtension\Literature\DOITools;
use DIQA\ChemExtension\Literature\LiteraturePageCreator;
use DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculesImportJob;
use DIQA\ChemExtension\Pages\ChemForm;
use DIQA\ChemExtension\Pages\ChemFormParser;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Pages\MoleculePageCreator;
use DIQA\ChemExtension\ParserFunctions\ParserFunctionParser;
use DIQA\ChemExtension\ParserFunctions\RenderLiterature;
use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;
use JobQueueGroup;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\User\UserIdentity;
use WikiPage;

class MultiContentSave
{


    public static function onPageSaveComplete( WikiPage $wikiPage, UserIdentity $user,
        string $summary, int $flags, RevisionRecord $revisionRecord, EditResult $editResult )
    {


        if ($revisionRecord == null || $revisionRecord->getContent(SlotRecord::MAIN) == null) {
            // something is wrong. there is no revision
            return;
        }
        $wikitext = $revisionRecord->getContent(SlotRecord::MAIN)->getWikitextForTransclusion();#

        self::parseChemicalFormulas($wikitext, $revisionRecord->getPageAsLinkTarget());
        self::parseParserFunctions($wikitext);
    }

    private static function parseParserFunctions($wikitext)
    {
        $logger = new LoggerUtils('MultiContentSave', 'ChemExtension');
        $parser = new ParserFunctionParser();
        $creator = new LiteraturePageCreator();
        $literatureFunctions = $parser->parseFunction('literature', $wikitext);

        foreach ($literatureFunctions as $f) {
            $doiAsText = $f['doi'] ?? null;
            $doi = DOITools::parseDOI($doiAsText);
            if (is_null($doi)) {
                continue;
            }

            try {
                $doiData = RenderLiterature::resolveDOI($doi);
                $creator->createPage($doi, $doiData);
            } catch (Exception $e) {
                $logger->error($e->getMessage());
            }
        }
    }

    /**
     * @param $wikitext
     */
    private static function parseChemicalFormulas($wikitext, $pageTitle): void
    {
        $logger = new LoggerUtils('AfterDataUpdateCompleteHandler', 'ChemExtension');
        $chemFormParser = new ChemFormParser();
        $chemForms = $chemFormParser->parse($wikitext);

        $pageCreator = new MoleculePageCreator();
        $moleculeCollections = [];
        self::removeAllFromChemFormIndex($pageTitle);
        foreach ($chemForms as $chemForm) {
            try {
                $moleculePage = $pageCreator->createNewMoleculePage($chemForm);
                self::addToChemFormIndex($pageTitle, $moleculePage['chemformId']);
                if ($chemForm->hasRGroupDefinitions()) {
                    $moleculeCollections[] = ['title' => $moleculePage['title'], 'chemForm' => $chemForm];
                }
            } catch (Exception $e) {
                $logger->error($e->getMessage());
            }
        }

        if (count($moleculeCollections) > 0) {
            self::addMoleculeCollectionJob($moleculeCollections);
        }
    }

    /**
     * @param ChemForm $chemForm
     * @param Title|null $title
     */
    private static function addMoleculeCollectionJob(array $moleculeCollections): void
    {
        global $wgTitle;

        $jobParams = [];
        $jobParams['moleculeCollections'] = $moleculeCollections;
        $job = new MoleculesImportJob($wgTitle, $jobParams);
        JobQueueGroup::singleton()->push($job);

    }

    private static function addToChemFormIndex($pageTitle, $chemformId)
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER );
        $repo = new ChemFormRepository($dbr);
        $repo->addChemFormToIndex($pageTitle, $chemformId);
    }

    private static function removeAllFromChemFormIndex($pageTitle)
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER );
        $repo = new ChemFormRepository($dbr);
        $repo->deleteAllChemFormIndexByPageId($pageTitle);
    }
}
