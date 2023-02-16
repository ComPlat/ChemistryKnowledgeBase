<?php

namespace DIQA\ChemExtension;

use DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculesImportJob;
use DIQA\ChemExtension\Pages\ChemForm;
use DIQA\ChemExtension\Pages\ChemFormParser;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Pages\MoleculePageCreator;
use DIQA\ChemExtension\ParserFunctions\ParserFunctionParser;
use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;
use JobQueueGroup;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\User\UserIdentity;
use WikiPage;
use Title;

class MultiContentSave
{

    private static $MOLECULES_FOUND = [];

    public static function onPageSaveComplete(WikiPage $wikiPage, UserIdentity $user,
                                              string $summary, int $flags, RevisionRecord $revisionRecord, EditResult $editResult)
    {


        if ($revisionRecord == null || $revisionRecord->getContent(SlotRecord::MAIN) == null) {
            // something is wrong. there is no revision
            return;
        }
        $wikitext = $revisionRecord->getContent(SlotRecord::MAIN)->getWikitextForTransclusion();#

        $pageTitle = $revisionRecord->getPageAsLinkTarget();
        self::removeAllMoleculesFromChemFormIndex($pageTitle);
        self::parseChemicalFormulas($wikitext, $pageTitle);
        self::parseMoleculeLinks($wikitext, $pageTitle);

        self::addMoleculesToIndex($pageTitle);
        self::resetCollectMolecules($pageTitle);
    }

    public static function resetCollectMolecules(Title $pageTitle)
    {
        unset(self::$MOLECULES_FOUND[$pageTitle->getPrefixedText()]);
    }

    public static function collectMolecules($chemFormId, $pageTitle)
    {
        self::$MOLECULES_FOUND[$pageTitle->getPrefixedText()][] = $chemFormId;
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

        foreach ($chemForms as $chemForm) {
            try {
                $moleculePage = $pageCreator->createNewMoleculePage($chemForm);
                self::collectMolecules($moleculePage['chemformId'], $pageTitle);
                if ($chemForm->hasRGroupDefinitions()) {
                    $moleculeCollections[] = ['title' => $moleculePage['title'], 'chemForm' => $chemForm];
                }
            } catch (Exception $e) {
                $logger->error($e->getMessage());
            }
        }

        if (count($moleculeCollections) > 0) {
            self::addMoleculeCollectionJob($moleculeCollections, $pageTitle);
        }
    }

    /**
     * @param ChemForm $chemForm
     * @param Title|null $title
     */
    private static function addMoleculeCollectionJob(array $moleculeCollections, Title $pageTitle): void
    {
        $jobParams = [];
        $jobParams['moleculeCollections'] = $moleculeCollections;
        $job = new MoleculesImportJob($pageTitle, $jobParams);
        JobQueueGroup::singleton()->push($job);

    }

    private static function addToChemFormIndex($pageTitle, $chemformId)
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER);
        $repo = new ChemFormRepository($dbr);
        $repo->addChemFormToIndex($pageTitle, $chemformId);
    }

    private static function removeAllMoleculesFromChemFormIndex($pageTitle)
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER);
        $repo = new ChemFormRepository($dbr);
        $repo->deleteAllChemFormIndexByPageId($pageTitle);
    }

    private static function parseMoleculeLinks($wikitext, Title $pageTitle)
    {
        $logger = new LoggerUtils('MultiContentSave', 'ChemExtension');
        $parser = new ParserFunctionParser();
        $moleculeLinks = $parser->parseFunction('moleculelink', $wikitext);
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER);
        $repo = new ChemFormRepository($dbr);

        foreach ($moleculeLinks as $f) {
            $link = $f['link'] ?? null;
            if (is_null($link)) {
                continue;
            }

            try {
                $chemFormId = $repo->getChemFormId($link);
                self::collectMolecules($chemFormId, $pageTitle);
            } catch (Exception $e) {
                $logger->error($e->getMessage());
            }
        }
    }

    /**
     * @param Title $pageTitle
     */
    private static function addMoleculesToIndex(Title $pageTitle): void
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER);
        $repo = new ChemFormRepository($dbr);
        $uniqueListOfMolecules = array_unique(self::$MOLECULES_FOUND[$pageTitle->getPrefixedText()] ?? []);

        foreach ($uniqueListOfMolecules as $chemFormId) {
            $repo->addChemFormToIndex($pageTitle, $chemFormId);
        }
    }
}
