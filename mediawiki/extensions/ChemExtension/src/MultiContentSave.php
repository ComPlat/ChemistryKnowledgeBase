<?php

namespace DIQA\ChemExtension;

use DIQA\ChemExtension\Literature\DOITools;
use DIQA\ChemExtension\Literature\LiteratureRepository;
use DIQA\ChemExtension\Pages\ChemFormParser;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Pages\MoleculePageCreator;
use DIQA\ChemExtension\ParserFunctions\ParserFunctionParser;
use DIQA\ChemExtension\Utils\ChemTools;
use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\TemplateEditor;
use DIQA\ChemExtension\Utils\TemplateParser\TemplateParser;
use DIQA\ChemExtension\Utils\TemplateParser\TemplateTextNode;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\User\UserIdentity;
use Title;
use User;
use WikiPage;

class MultiContentSave
{

    public static $MOLECULES_FOUND = [];

    public static function onPageSaveComplete(WikiPage $wikiPage, UserIdentity $user,
                                              string $summary, int $flags, RevisionRecord $revisionRecord, EditResult $editResult)
    {


        if ($revisionRecord == null || $revisionRecord->getContent(SlotRecord::MAIN) == null) {
            // something is wrong. there is no revision
            return;
        }
        $wikitext = $revisionRecord->getContent(SlotRecord::MAIN)->getWikitextForTransclusion();#
        $pageTitle = $revisionRecord->getPageAsLinkTarget();
        self::removeSubpagesIfNecessary($wikiPage, $wikitext);
        self::parseContentAndUpdateIndex($wikitext, $pageTitle, true);
    }

    public static function onArticleDeleteComplete( &$article, User &$user, $reason, $id, $content, \LogEntry
        $logEntry, $archivedRevisionCount ) {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER);
        $repo = new ChemFormRepository($dbr);
        $repo->deleteAllChemFormIndexByPage($article->getTitle());
        $repo->deleteAllConcreteMoleculeByMoleculePage($article->getTitle());
        self::removeAllLiteratureReferencesFromIndex($article->getTitle());
        if ($article->getTitle()->getNamespace() === NS_MOLECULE
        || $article->getTitle()->getNamespace() === NS_REACTION) {
            $repo->deleteChemForm($article->getTitle()->getText());
        }
    }

    public static function onPageMoveComplete(
        LinkTarget $old,
        LinkTarget $new,
        UserIdentity $userIdentity,
        int $pageid,
        int $redirid,
        string $reason,
        RevisionRecord $revision
    ) {
        $oldTitle = Title::newFromText($old->getText(), $old->getNamespace());
        $newTitle = Title::newFromText($new->getText(), $new->getNamespace());
        if ($oldTitle->isSubpage()) {
            $baseTitle = $oldTitle->getBaseTitle();
            $wikitext = WikiTools::getText($baseTitle);
            $parser = new ParserFunctionParser();
            $newWikitext = $parser->replaceFunction($wikitext, 'experimentlist',
                'name', $oldTitle->getSubpageText(), ['name' => $newTitle->getSubpageText()]);
            if ($wikitext !== $newWikitext) {
                WikiTools::doEditContent($baseTitle, $newWikitext, "auto-updated");
            }
        }
    }


    public static function collectMolecules($chemFormId, $pageTitle)
    {
        if (!in_array($chemFormId, self::$MOLECULES_FOUND)) {
            self::$MOLECULES_FOUND[] = $chemFormId;
        }
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

        foreach ($chemForms as $chemForm) {
            try {
                if ($createPages) {
                    $moleculePage = $pageCreator->createNewMoleculePage($chemForm, $pageTitle,null, true);

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


    }


    private static function removeAllMoleculesFromChemFormIndex($pageTitle)
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER);
        $repo = new ChemFormRepository($dbr);
        $repo->deleteAllChemFormIndexByPage($pageTitle);
    }

    public static function removeAllLiteratureReferencesFromIndex($pageTitle)
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $repo = new LiteratureRepository($dbr);
        $repo->deleteIndexForPage($pageTitle);
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

    public static function parseAndUpdateLiteratureReferences($wikitext, Title $pageTitle)
    {

        $parser = new ParserFunctionParser();
        $literatureReferences = $parser->parseFunction('literature', $wikitext);
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $repo = new LiteratureRepository($dbr);

        foreach ($literatureReferences as $f) {
            $doi = $f['doi'] ?? null;
            if (is_null($doi)) {
                continue;
            }
            // fixes "broken" DOIs
            $doi = DOITools::parseDOI($doi);
            $repo->addToLiteratureIndex($doi, $pageTitle);
        }

        $te = new TemplateEditor($wikitext);
        $params = $te->getTemplateParams("DOI");
        if (array_key_exists('doi', $params)) {
            // fixes "broken" DOIs
            $doi = DOITools::parseDOI($params['doi']);
            $repo->addToLiteratureIndex($doi, $pageTitle);
        }
    }

    /**
     * @param Title $pageTitle
     */
    private static function addMoleculesToIndex(Title $pageTitle): void
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER);
        $repo = new ChemFormRepository($dbr);
        $uniqueListOfMolecules = self::$MOLECULES_FOUND;

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
        self::removeAllLiteratureReferencesFromIndex($pageTitle);
        self::parseChemicalFormulas($wikitext, $pageTitle, $createPages);
        self::parseMoleculeLinks($wikitext, $pageTitle);
        self::parseAndUpdateLiteratureReferences($wikitext, $pageTitle);

        self::addMoleculesToIndex($pageTitle);
        self::addToCategoryIndex($pageTitle);
        if ($pageTitle->isSubpage()) {
            self::addMoleculesFromInvestigation($wikitext, $pageTitle);
        }
        Setup::cleanupChemExtState();
    }

    private static function removeSubpagesIfNecessary(WikiPage $pageTitle, string $wikitext)
    {
        $logger = new LoggerUtils('MultiContentSave', 'ChemExtension');
        $subPages = $pageTitle->getTitle()->getSubpages();
        $parser = new ParserFunctionParser();
        $experiments = $parser->parseFunction('experimentlist', $wikitext);
        $experimentNames = array_map(fn($e) => str_replace('_', ' ', $e['name'] ?? ''), $experiments);
        foreach($subPages as $subPage) {
            $logger->log('check unused investigation: ' . $subPage->getSubpageText());
            if (!in_array($subPage->getSubpageText(), $experimentNames)) {
                $deletePage = MediaWikiServices::getInstance()->getDeletePageFactory()
                    ->newDeletePage($subPage->toPageIdentity(), \RequestContext::getMain()->getUser());
                $logger->log('Delete unused investigation: ' . $subPage->getSubpageText());
                //$deletePage->deleteIfAllowed("unused investigation page");
            }
        }
    }

}
