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
    private MWFileReader $fileReader;
    private BoostingCalculator $boostingCalculator;

    public function __construct()
    {
        $this->smwReader = new SMWReader();
        $this->fileReader = new MWFileReader();
        $this->boostingCalculator = new BoostingCalculator();
    }

    /**
     * Retrieves the display title from the properties table for the given page.
     * It will probably only properly work if the DisplayTitles extension is installed and used.
     * The default value is the pagename.
     *
     * This code is inspired by getDisplayTitle() from DisplayTitle\includes\DisplayTitleHooks.php
     *
     * @param Title $title
     * @param ?WikiPage $wikipage (optional) if present redirects will be followed
     * @return string smwh_displaytitle
     */
    public static function findDisplayTitle(Title $title, WikiPage $wikipage = null): string
    {
        $title = $title->createFragmentTarget('');
        $originalPageName = $title->getText();

        $redirect = false;
        if ($wikipage) {
            $redirectTarget = MediaWikiServices::getInstance()->getRedirectLookup()->getRedirectTarget($wikipage);
            if (!is_null($redirectTarget)) {
                $redirect = true;
                $title = Title::makeTitle($redirectTarget->getNamespace(), $redirectTarget->getDBkey());
            }
        }

        $id = $title->getArticleID();
        $values = MediaWikiServices::getInstance()->getPageProps()->getProperties($title, 'displaytitle');

        if (array_key_exists($id, $values)) {
            $value = $values[$id];
            if (trim(str_replace('&#160;', '', strip_tags($value))) !== '') {
                return $value;
            }
        } elseif ($redirect) {
            return $title->getPrefixedText();
        }
        return $originalPageName;
    }


    public function fromWikiPage(WikiPage $wikiPage, array &$messages = []): Document
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
        $text = $this->getText($wikiPage, $doc, $messages);

        $doc['id'] = $pageID;
        $doc['smwh_namespace_id'] = $pageNamespace;
        $doc['smwh_title'] = $pageDbKey;
        $doc['smwh_full_text'] = $text;
        $doc['smwh_displaytitle'] = self::findDisplayTitle($pageTitle, $wikiPage);

        if ($pageTitle->exists()) {
            $this->smwReader->retrievePropertyValues($pageTitle, $doc);
            $this->indexCategories($pageTitle, $doc);
        }

        $options = [];
        $doc['smwh_templates'] = $this->retrieveTemplates($pageID);
        $this->boostingCalculator->calculateBoosting($wikiPage, $doc['smwh_templates'], $options, $doc);

        $hookContainer = MediaWikiServices::getInstance()->getHookContainer();
        $hookContainer->run('fs_saveArticle', [$text, &$doc]);

        $document = new Document($doc['id'],
            $doc['smwh_title'],
            $doc['smwh_displaytitle'],
            $doc['smwh_namespace_id']);
        $document->setPropertyValues($doc['smwh_properties'] ?? [])
            ->setCategories($doc['smwh_categories'] ?? [])
            ->setDirectCategories($doc['smwh_directcategories'] ?? [])
            ->setTemplates($doc['smwh_templates'] ?? [])
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

            // suppress warning due to old impl. of SMW\MediaWiki\Content\SchemaContent
            @$parserOut = MediaWikiServices::getInstance()->getContentRenderer()->getParserOutput($content, $wikiPage, $revision->getId());
        } else {
            // index latest revision
            $content = $wikiPage->getContent();
            // suppress warning due to old impl. of SMW\MediaWiki\Content\SchemaContent
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

        $directCategories = array_keys($title->getParentCategories());
        $directCategories = array_filter($directCategories, fn($category) => !$this->smwReader->shouldBeIgnored(Title::newFromText($category)));
        $doc['smwh_directcategories'] = array_map(fn($category) => Title::newFromText($category)->getDBkey(), $directCategories);

        // index all parent categories as super-categories
        $superCategories = ArrayTools::arrayFlattenToKeyValues($title->getParentCategoryTree());
        $superCategories = array_filter($superCategories, fn($category) => !$this->smwReader->shouldBeIgnored(Title::newFromText($category)));
        $superCategories = array_map(fn($category) => Title::newFromText($category)->getDBkey(), $superCategories);
        $doc['smwh_categories'] = array_unique(array_merge($doc['smwh_directcategories'], $superCategories));

    }

    public function retrieveTemplates($pageId): array
    {
        $db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $res = $db->newSelectQueryBuilder()
            ->select('CAST(lt_title AS CHAR) AS template')
            ->from('templatelinks')
            ->join('page', null, ['page_id = tl_from'])
            ->join('linktarget', null, ['lt_id = tl_target_id'])
            ->where("tl_from = $pageId")
            ->caller(__METHOD__)
            ->fetchResultSet();

        $smwhTemplates = [];
        if ($res->numRows() > 0) {
            while ($row = $res->fetchObject()) {
                $template = $row->template;
                $smwhTemplates[] = str_replace("_", " ", $template);
            }
        }
        $res->free();

        return array_unique($smwhTemplates);
    }


    public function getCategoryTuples()
    {
        $db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $CATEGORY_NAMESPACE = NS_CATEGORY;
        $sql = <<<SQL
SELECT DISTINCT page_from.page_title AS from_category, cl_to AS to_category,
       props_from.pp_value AS from_displaytitle, props_to.pp_value AS to_displaytitle

FROM page page_from
LEFT JOIN categorylinks ON page_from.page_id = categorylinks.cl_from
LEFT JOIN page_props props_from ON props_from.pp_page = page_from.page_id AND props_from.pp_propname = 'displaytitle'

LEFT JOIN page AS page_to ON categorylinks.cl_to = page_to.page_title AND page_to.page_namespace = $CATEGORY_NAMESPACE
LEFT JOIN page_props props_to ON props_to.pp_page = page_to.page_id AND props_to.pp_propname = 'displaytitle'
WHERE page_from.page_namespace = $CATEGORY_NAMESPACE
SQL;

        $res = $db->query($sql);
        $results = [];
        foreach ($res as $row) {
            $results[] =
                [
                    'from' => $row->from_category,
                    'to' => $row->to_category,
                    'from_displaytitle' => $row->from_displaytitle ?? str_replace("_", " ", $row->from_category),
                    'to_displaytitle' => $row->to_displaytitle ?? str_replace("_", " ", $row->to_category ?? ''),
                ];

        }
        return $results;
    }
}