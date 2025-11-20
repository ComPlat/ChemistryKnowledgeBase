<?php

namespace DIQA\FacetedSearch2\Update;

use DIQA\FacetedSearch2\Model\Update\Document;
use DIQA\FacetedSearch2\Utils\ArrayTools;
use Exception;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
use Sanitizer;
use WikiPage;

class MWDBReader
{
    private SMWReader $smwReader;
    private FileReader $fileReader;
    private BoostingCalculator $boostingCalculator;

    public function __construct()
    {
        $this->smwReader = new SMWReader();
        $this->fileReader = new FileReader();
        $this->boostingCalculator = new BoostingCalculator();
    }


    /**
     * Updates the index for the given $wikiPage.
     * It retrieves all semantic data of the new version and adds it to the index.
     *
     * @param WikiPage $wikiPage
     *         The article that changed.
     * @param string|null $rawText
     *        Optional content of the article. If it is null, the content of $wikiPage is
     *        retrieved in this method.
     * @param array $messages
     *      User readable messages (out)
     * @throws Exception
     */
    public function fromWikiPage(WikiPage $wikiPage, string $rawText = null, array &$messages = []
    ): Document
    {

        $doc = [];

        $pageTitle = $wikiPage->getTitle();
        $pagePrefixedTitle = $pageTitle->getPrefixedText();
        $pageID = $wikiPage->getId();
        if ($pageID == 0) {
            throw new Exception("invalid page ID for $pagePrefixedTitle");
        }

        global $fs2gBlacklistPages;
        if (in_array($pagePrefixedTitle, $fs2gBlacklistPages)) {
            throw new Exception("blacklisted page: $pagePrefixedTitle");
        }

        $pageNamespace = $pageTitle->getNamespace();
        $pageDbKey = $pageTitle->getDBkey();
        $text = $rawText ?? $this->getText($wikiPage, $doc, $messages);

        $doc['id'] = $pageID;
        $doc['smwh_namespace_id'] = $pageNamespace;
        $doc['smwh_title'] = $pageDbKey;
        $doc['smwh_full_text'] = $text;
        $doc['smwh_displaytitle'] = FacetedSearchUtil::findDisplayTitle($pageTitle, $wikiPage);

        if ($pageTitle->exists()) {
            $this->smwReader->retrievePropertyValues($pageTitle, $doc);
            $this->indexCategories($pageTitle, $doc);
        }

        $options = [];
        $this->boostingCalculator->calculateBoosting($wikiPage, $options, $doc);

        $hookContainer = MediaWikiServices::getInstance()->getHookContainer();
        $hookContainer->run('fs_saveArticle', [$text, &$doc]);

        $document = new Document($doc['id'],
            $doc['smwh_title'],
            $doc['smwh_displaytitle'],
            $doc['smwh_namespace_id']);
        $document->setPropertyValues($doc['smwh_properties'] ?? [])
            ->setCategories($doc['smwh_categories'] ?? [])
            ->setDirectCategories($doc['smwh_directcategories'] ?? [])
            ->setBoost($options['smwh_boost_dummy']['boost'] ?? 1.0)
            ->setFulltext($doc['smwh_full_text']);

        return $document;
    }

    private function getText(WikiPage $wikiPage, array &$doc, array &$messages): string
    {
        $pageTitle = $wikiPage->getTitle();
        $pageNamespace = $pageTitle->getNamespace();

        if ($pageNamespace == NS_FILE) {
            $text = $this->fileReader->getTextFromFile($wikiPage, $doc, $messages);
            if ($text) {
                return $text;
            }
        }

        global $egApprovedRevsBlankIfUnapproved, $egApprovedRevsNamespaces;
        if (defined('APPROVED_REVS_VERSION')
            && $egApprovedRevsBlankIfUnapproved
            && in_array($pageNamespace, $egApprovedRevsNamespaces)) {

            // index the approved revision
            $revision = $this->getApprovedRevision($wikiPage);
            if (is_null($revision)) {
                throw new Exception("unapproved $pageTitle");
            }
            $content = $revision->getContent(SlotRecord::MAIN, RevisionRecord::RAW);

            // supress warning due to old impl. of SMW\MediaWiki\Content\SchemaContent
            @$parserOut = MediaWikiServices::getInstance()->getContentRenderer()->getParserOutput($content, $wikiPage, $revision->getId());
        } else {
            // index latest revision
            $content = $wikiPage->getContent();
            // supress warning due to old impl. of SMW\MediaWiki\Content\SchemaContent
            @$parserOut = MediaWikiServices::getInstance()->getContentRenderer()->getParserOutput($content, $wikiPage);
        }

        if (!$parserOut) {
            return '';
        } else {
            return Sanitizer::stripAllTags($parserOut->getText());
        }
    }

    private function getApprovedRevision(WikiPage $wikiPage): ?RevisionRecord
    {
        // get approved rev_id
        $db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);

        $res = $db->newSelectQueryBuilder()
            ->select('rev_id')
            ->from('approved_revs')
            ->where('page_id = ' . $wikiPage->getTitle()->getArticleID())
            ->fetchResultSet();

        $rev_id = null;
        if ($res->numRows() > 0 && $row = $res->fetchRow()) {
            $rev_id = $row['rev_id'];
        }

        if (is_null($rev_id)) {
            return null;
        }

        $store = MediaWikiServices::getInstance()->getRevisionStore();
        return $store->getRevisionById($rev_id);
    }

    private function indexCategories(Title $title, array &$doc): void
    {

        $directCategories = array_values($title->getParentCategories());
        $directCategories = array_filter($directCategories, fn($category) => !$this->smwReader->shouldBeIgnored(Title::newFromText($category)));
        $doc['smwh_directcategories'] = array_map(fn($category) => Title::newFromText($category)->getDBkey(), $directCategories);

        // index all parent categories as super-categories
        $superCategories = ArrayTools::arrayFlattenToKeyValues($title->getParentCategoryTree());
        $superCategories = array_filter($superCategories, fn($category) => !$this->smwReader->shouldBeIgnored(Title::newFromText($category)));
        $superCategories = array_map(fn($category) => Title::newFromText($category)->getDBkey(), $superCategories);
        $doc['smwh_categories'] = array_unique(array_merge($doc['smwh_directcategories'], $superCategories));

    }

}