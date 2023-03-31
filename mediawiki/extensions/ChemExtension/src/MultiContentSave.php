<?php

namespace DIQA\ChemExtension;

use DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculesImportJob;
use DIQA\ChemExtension\Pages\ChemForm;
use DIQA\ChemExtension\Pages\ChemFormParser;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Pages\MoleculePageCreator;
use DIQA\ChemExtension\ParserFunctions\ParserFunctionParser;
use DIQA\ChemExtension\Utils\ChemTools;
use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\TemplateParser\TemplateParser;
use DIQA\ChemExtension\Utils\TemplateParser\TemplateTextNode;
use DIQA\ChemExtension\Utils\WikiTools;
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
        self::parseContentAndUpdateIndex($wikitext, $pageTitle, true);
    }

    public static function onArticleDeleteComplete( &$article, \User &$user, $reason, $id, $content, \LogEntry
        $logEntry, $archivedRevisionCount ) {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER);
        $repo = new ChemFormRepository($dbr);
        $repo->deleteAllChemFormIndexByPage($article->getTitle());
        if ($article->getTitle()->getNamespace() === NS_MOLECULE
        || $article->getTitle()->getNamespace() === NS_REACTION) {
            $repo->deleteChemForm($article->getTitle()->getText());
        }
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
    private static function parseChemicalFormulas($wikitext, $pageTitle, $createPages): void
    {
        $logger = new LoggerUtils('AfterDataUpdateCompleteHandler', 'ChemExtension');
        $chemFormParser = new ChemFormParser();
        $chemForms = $chemFormParser->parse($wikitext);

        $pageCreator = new MoleculePageCreator();
        $moleculeCollections = [];

        foreach ($chemForms as $chemForm) {
            try {
                if ($createPages) {
                    $moleculePage = $pageCreator->createNewMoleculePage($chemForm, null, true);
                    if ($chemForm->hasRGroupDefinitions()) {
                        $moleculeCollections[] = ['title' => $moleculePage['title'], 'chemForm' => $chemForm];
                    }
                    self::collectMolecules($moleculePage['chemformId'], $pageTitle);
                } else {
                    $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER);

                    $repo = new ChemFormRepository($dbr);
                    $moleculeKey = $chemForm->getMoleculeKey();
                    $chemFormId = $repo->getChemFormId($moleculeKey);
                    if (!is_null($chemFormId)) {
                        self::collectMolecules($chemFormId, $pageTitle);
                    }
                }
            } catch (Exception $e) {
                $logger->error($e->getMessage());
            }
        }

        if (count($moleculeCollections) > 0 && $createPages) {
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

    private static function removeAllMoleculesFromChemFormIndex($pageTitle)
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER);
        $repo = new ChemFormRepository($dbr);
        $repo->deleteAllChemFormIndexByPage($pageTitle);
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

    private static function addToCategoryIndex(Title $pageTitle)
    {
        $categories = [];
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER);
        $repo = new CategoryIndexRepository($dbr);
        $repo->deleteCategoryFromIndex($pageTitle);
        WikiTools::getReversedCategoryList($pageTitle->getParentCategoryTree(), $categories);
        $repo->addCategoriesForTitle($pageTitle, $categories);
    }

    private static function addMoleculesFromInvestigation(string $wikitext, Title $pageTitle)
    {
        $moleculesAlreadyFound = [];
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER);
        $repo = new ChemFormRepository($dbr);
        $templateParser = new TemplateParser($wikitext);
        $ast = $templateParser->parse();
        $ast->visitNodes(function($node) use($repo, $pageTitle, & $moleculesAlreadyFound) {
            if (!($node instanceof TemplateTextNode)) return;

            $params = explode('|', $node->getText());
            $keyValues = ParserFunctionParser::parseArguments($params);
            foreach($keyValues as $key => $value) {
                $chemFormId = ChemTools::getChemFormIdFromPageTitle($value);
                if (!is_null($chemFormId) && !in_array($chemFormId, $moleculesAlreadyFound)) {
                    $repo->addChemFormToIndex($pageTitle, $chemFormId);
                    $moleculesAlreadyFound[] = $chemFormId;
                }
            }
        });

    }

    public static function parseContentAndUpdateIndex(string $wikitext, Title $pageTitle, bool $createPages) {
        self::removeAllMoleculesFromChemFormIndex($pageTitle);
        self::parseChemicalFormulas($wikitext, $pageTitle, $createPages);
        self::parseMoleculeLinks($wikitext, $pageTitle);

        self::addMoleculesToIndex($pageTitle);
        self::addToCategoryIndex($pageTitle);
        if ($pageTitle->isSubpage()) {
            self::addMoleculesFromInvestigation($wikitext, $pageTitle);
        }
        self::resetCollectMolecules($pageTitle);
    }

}
