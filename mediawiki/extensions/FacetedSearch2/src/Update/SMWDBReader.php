<?php

namespace DIQA\FacetedSearch2\Update;

use DIQA\FacetedSearch2\ConfigTools;
use DIQA\FacetedSearch2\Model\Common\Datatype;
use DIQA\FacetedSearch2\Model\Common\MWTitle;
use DIQA\FacetedSearch2\Model\Common\Property;
use DIQA\FacetedSearch2\Model\Update\Document;
use DIQA\FacetedSearch2\Model\Update\PropertyValues;
use DIQA\FacetedSearch2\Setup;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use Sanitizer;
use SMW\DIProperty as SMWDIProperty;
use SMW\DIWikiPage as SMWDIWikiPage;
use SMW\PropertyRegistry;
use SMWDataItem;
use Title;
use WikiPage;

class SMWDBReader {

    /**
     * Updates the index for the given $wikiPage.
     * It retrieves all semantic data of the new version and adds it to the index.
     *
     * @param WikiPage $wikiPage
     *         The article that changed.
     * @param string $rawText
     *        Optional content of the article. If it is null, the content of $wikiPage is
     *        retrieved in this method.
     * @param array $messages
     *      User readible messages (out)
     */
    public function getIndexDocumentFromWikiPage(WikiPage $wikiPage, $rawText = null,
                                          &$messages = []
    ) : Document {

        $doc = [];

        $pageTitle = $wikiPage->getTitle();
        $pagePrefixedTitle = $pageTitle->getPrefixedText();
        $pageID = $wikiPage->getId();
        if( $pageID == 0 ) {
            throw new Exception("invalid page ID for $pagePrefixedTitle");
        }

        global $fs2gBlacklistPages;
        if (in_array($pagePrefixedTitle, $fs2gBlacklistPages)) {
            throw new Exception("blacklisted page: $pagePrefixedTitle");
        }

        $pageNamespace = $pageTitle->getNamespace();
        $pageDbKey  = $pageTitle->getDBkey();
        $text = $rawText ?? $this->getText( $wikiPage, $doc, $messages );

        $doc['id'] = $pageID;
        $doc['smwh_namespace_id'] = $pageNamespace;
        $doc['smwh_title'] = $pageDbKey;
        $doc['smwh_full_text'] = $text;
        $doc['smwh_displaytitle'] = FacetedSearchUtil::findDisplayTitle( $pageTitle, $wikiPage );

        if ($this->retrieveSMWID($pageNamespace, $pageDbKey, $doc)) {
            $this->retrievePropertyValues($pageTitle, $doc);
            $this->indexCategories($pageTitle, $doc);
        }

        $options = [];
        $this->calculateBoosting( $wikiPage, $options, $doc );

        // call fs_saveArticle hook
        $hookContainer = MediaWikiServices::getInstance()->getHookContainer();
        $hookContainer->run( 'fs_saveArticle', [ $text, &$doc ] );

        $document = new Document($doc['id'],
            $doc['smwh_title'],
            $doc['smwh_displaytitle'],
            $doc['smwh_namespace_id']);
        $document->setPropertyValues($doc['smwh_properties'] ?? [])
                ->setCategories($doc['smwh_categories']?? [])
                ->setDirectCategories($doc['smwh_directcategories']?? [])
                ->setBoost($options['smwh_boost_dummy']['boost'] ?? 1.0)
                ->setFulltext($doc['smwh_full_text']);

        return $document;
    }

    private function getText(WikiPage $wikiPage, array &$doc, array &$messages ) : string {
        $pageTitle = $wikiPage->getTitle();
        $pageNamespace = $pageTitle->getNamespace();

        if ($pageNamespace == NS_FILE) {
            $text = $this->getTextFromFile( $wikiPage, $doc, $messages );
            if( $text ) {
                return $text;
            }
        }

        global $egApprovedRevsBlankIfUnapproved, $egApprovedRevsNamespaces;
        if (defined('APPROVED_REVS_VERSION')
            && $egApprovedRevsBlankIfUnapproved
            && in_array( $pageNamespace, $egApprovedRevsNamespaces )) {

            // index the approved revision
            $revision = $this->getApprovedRevision( $wikiPage );
            if ($revision === false) {
                throw new Exception( "unapproved $pageTitle" );
            }
            $content = $revision->getContent( SlotRecord::MAIN, RevisionRecord::RAW );

            // supress warning due to old impl. of SMW\MediaWiki\Content\SchemaContent
            @$parserOut = MediaWikiServices::getInstance()->getContentRenderer()->getParserOutput( $content, $wikiPage, $revision->getId() );
        } else {
            // index latest revision
            $content = $wikiPage->getContent();
            // supress warning due to old impl. of SMW\MediaWiki\Content\SchemaContent
            @$parserOut = MediaWikiServices::getInstance()->getContentRenderer()->getParserOutput( $content, $wikiPage );
        }

        if ( !$parserOut ) {
            return '';
        } else {
            return Sanitizer::stripAllTags($parserOut->getText());
        }
    }

    /**
     * extract document if a file was uploaded
     */
    private function getTextFromFile( WikiPage $wikiPage, array &$doc, array &$messages ) : string {
        $pageTitle = $wikiPage->getTitle();
        $pageNamespace = $pageTitle->getNamespace();
        if ($pageNamespace !== NS_FILE) {
            return '';
        }

        $text = '';
        $pageDbKey  = $pageTitle->getDBkey();

        global $fs2gIndexImageURL;

        try {
            if (isset($fs2gIndexImageURL) && $fs2gIndexImageURL === true) {
                $this->retrieveFileSystemPath($pageNamespace, $pageDbKey, $doc);
            }
            $client = ConfigTools::getFacetedSearchClient();
            $metadata = $this->getDocumentMetadata($pageTitle);
            if (is_null($metadata)) return '';
            $text = $client->requestFileExtraction( file_get_contents($metadata['filePath']), $metadata['contentType'] );

        } catch( Exception $e ) {
            $messages[] = $e->getMessage();
            $text = $e->getMessage();
        }

        return $text;
    }

    private function getDocumentMetadata($title)
    {
        if ($title instanceof Title) {
            $file = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo()->newFile($title);
            $filepath = $file->getLocalRefPath();
        } else {
            $filepath = $title;
        }

        // get file and extension
        $ext = pathinfo($filepath, PATHINFO_EXTENSION);

        // choose content type
        if ($ext == 'pdf') {
            $contentType = 'application/pdf';
        } else if ($ext == 'doc' || $ext == 'docx') {
            $contentType = 'application/msword';
        } else if ($ext == 'ppt' || $ext == 'pptx') {
            $contentType = 'application/vnd.ms-powerpoint';
        } else if ($ext == 'xls' || $ext == 'xlsx') {
            $contentType = 'application/vnd.ms-excel';
        } else {
            // general binary data as fallback (don't know if Tika accepts it)
            $contentType = 'application/octet-stream';
        }

        // do not index unknown formats
        if ($contentType == 'application/octet-stream') {
            return null;
        }

        // send document to Tika and extract text
        if ($filepath == '') {
            if (PHP_SAPI === 'cli' && !defined('UNITTEST_MODE')) {
                throw new Exception(sprintf("\nWARN  Empty file path for '%s'. Can not index document properly.\n", $title->getPrefixedText()));
            }
            return null;
        }

        return ['filePath' => $filepath, 'contentType' => $contentType];
    }

        /**
     * Will update the $options['smwh_boost_dummy']['boost'] field with the accumulated boost value
     * from namespaces, templates and categories of the wiki page.
     */
    private function calculateBoosting(WikiPage $wikiPage, array &$options, array $doc) {
        global $fs2gActivateBoosting;
        if (! isset($fs2gActivateBoosting) || !$fs2gActivateBoosting ) {
            return;
        }

        global $fs2gDefaultBoost;
        if($fs2gDefaultBoost) {
            $options['smwh_boost_dummy']['boost'] = $fs2gDefaultBoost;
        } else {
            $options['smwh_boost_dummy']['boost'] = 1.0;
        }

        $title = $wikiPage->getTitle();
        $namespace = $title->getNamespace();
        $pid = $wikiPage->getId();

        // add boost according to namespace
        global $fs2gNamespaceBoosts;
        if( array_key_exists($namespace, $fs2gNamespaceBoosts) ) {
            $this->updateBoostFactor($options, $fs2gNamespaceBoosts[$namespace]);
        }

        $db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );

        // add boost according to templates
        global $fs2gTemplateBoosts;
        $templates = $this->retrieveTemplates($db, $pid, $doc, $options);
        $templates = array_intersect(array_keys($fs2gTemplateBoosts), $templates);
        foreach($templates as $template) {
            $this->updateBoostFactor($options, $fs2gTemplateBoosts[$template]);
        }

        // add boost according to categories
        global $fs2gCategoryBoosts;
        $categoriesIterator = $wikiPage->getCategories();
        $categories = array();
        foreach ($categoriesIterator as $categoryTitle) {
            $categories[] = $categoryTitle;
        }
        $categories = array_intersect(array_keys($fs2gCategoryBoosts), $categories);
        foreach($categories as $category) {
            $this->updateBoostFactor($options, $fs2gCategoryBoosts[$category]);
        }
    }

    /**
     * @param WikiPage
     * @return RevisionRecord|bool
     */
    private function getApprovedRevision(WikiPage $wikiPage) {
        // get approved rev_id
        $db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );

        $res = $db->newSelectQueryBuilder()
            ->select( 'rev_id' )
            ->from( 'approved_revs' )
            ->where( 'page_id = ' . $wikiPage->getTitle()->getArticleID() )
            ->fetchResultSet();

        $rev_id = null;
        if ( $res->numRows() > 0 ) {
            if( $row = $res->fetchRow() ) {
                $rev_id = $row['rev_id'];
            }
        }

        if (is_null($rev_id)) {
            return false;
        }

        $store = MediaWikiServices::getInstance()->getRevisionStore();
        $revision = $store->getRevisionById( $rev_id );
        return $revision;
    }


    //--- Private methods ---

    /**
     * Add general boost factor if it is greater than the old.
     *
     * @param array $options
     * @param float $value
     */
    private function updateBoostFactor(array &$options, $value) {
        $options['smwh_boost_dummy']['boost'] *= $value;
    }

    /**
     * Retrieves the templates of the article with the page ID $pid and calculate
     * boosting factors for it
     *
     * @param IDatabase $db
     *         The database object
     * @param int $pid
     *         The page ID.
     * @param array $doc
     *
     * @param $options
     */
    private function retrieveTemplates($db, $pid, array &$doc, array &$options) {

        $res = $db->newSelectQueryBuilder()
            ->select( 'CAST(lt_title AS CHAR) AS template' )
            ->from( 'templatelinks' )
            ->join( 'page', null, [ 'page_id = tl_from' ] )
            ->join( 'linktarget', null, [ 'lt_id = tl_target_id' ] )
            ->where( "tl_from = $pid" )
            ->caller( __METHOD__ )
            ->fetchResultSet();

        $smwhTemplates = [];
        if ( $res->numRows() > 0 ) {
            while( $row = $res->fetchObject() ) {
                $template = $row->template;
                $smwhTemplates[] = str_replace("_", " ", $template);
            }
        }
        $res->free();

        return $smwhTemplates;
    }


    /**
     * Retrieves the SMW-ID of the article with the $namespaceID and the $title
     * and adds them to the document description $doc.
     *
     * @param int $namespaceID
     *         Namespace ID of the article
     * @param string $title
     *         The DB key of the title of the article
     * @param array $doc
     *         The document description. If there is a SMW ID for the article, it is
     *         added with the key 'smwh_smw_id'.
     * @return bool
     *         <true> if an SMW-ID was found
     *         <false> otherwise
     */
    private function retrieveSMWID( $namespaceID, $title, array &$doc ) {
        $db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
        $title = $db->addQuotes($title);
        $res = $db->newSelectQueryBuilder()
            ->select( 'smw_id' )
            ->from( 'smw_object_ids' )
            ->where( ["smw_namespace = $namespaceID", "smw_title=$title"] )
            ->caller( __METHOD__ )
            ->fetchResultSet();

        $found = false;
        if ( $res->numRows() > 0 ) {
            $row = $res->fetchObject();
            $smwID = $row->smw_id;
            $doc['smwh_smw_id'] = $smwID;
            $found = true;
        }
        $res->free();

        return $found;
    }

    /**
     * Retrieves full URL of the file resource attached to this title.
     *
     * @param int $namespace namespace-id
     * @param string $title dbkey
     * @param array $doc (out)
     */
    private function retrieveFileSystemPath($namespace, $title, array &$doc) {
        $title = Title::newFromText($title, $namespace);
        $file = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo()->newFile($title);
        $filepath = $file->getFullUrl();

        $doc['smwh_properties'][] = new PropertyValues(new Property("Diqa import fullpath", Datatype::STRING), [$filepath]);
    }

    /**
     * Retrieves the relations of the article with the SMW ID $smwID and adds
     * them to the document description $doc.
     *
     * @param Title $title
     * @param array $doc
     *         The document description. If the page has relations, all relations
     *         and their values are added to $doc. The key 'smwh_properties' will
     *         be an array of relation names and a key will be added for each
     *         relation with the value of the relation.
     */
    private function retrievePropertyValues( $title, array &$doc ) {
        global $fs2gIndexPredefinedProperties;

        $store = smwfGetStore();
        $propertyValuesToAdd = [];

        $subject = SMWDIWikiPage::newFromTitle($title);
        $properties = $store->getProperties($subject);

        foreach($properties as $property) {
            // skip instance-of and subclass properties
            if ($property->getKey() == "_INST" || $property->getKey() == "_SUBC") {
                continue;
            }

            // check if particular pre-defined property should be indexed
            $predefPropType = PropertyRegistry::getInstance()->getPropertyValueTypeById($property->getKey());
            $p = $property; //SMWDIProperty::newFromUserLabel($prop);
            if (!empty($predefPropType)) {
                // This is a predefined property
                if (isset($fs2gIndexPredefinedProperties) && $fs2gIndexPredefinedProperties === false) {
                    continue;
                }
            }

            // check if property should be indexed
            $prop_ignoreasfacet = wfMessage('fs_prop_ignoreasfacet')->text();

            $iafValues = $store->getPropertyValues($p->getDiWikiPage(), SMWDIProperty::newFromUserLabel($prop_ignoreasfacet));
            if (count($iafValues) > 0) {
                continue;
            }

            // retrieve all annotations and index them
            $values = $store->getPropertyValues($subject, $property);

            foreach($values as $value) {
                if ($value->getDIType() == SMWDataItem::TYPE_WIKIPAGE) {

                    if ($value->getSubobjectName() != "") {

                        global $fs2gIndexSubobjects;
                        if ($fs2gIndexSubobjects !== true) {
                            continue;
                        }

                        // handle record properties
                        if ($value->getSubobjectName() != "") {
                            $subData = smwfGetStore()->getSemanticData($value);
                            $recordProperties = $subData->getProperties();
                            foreach($recordProperties as $rp) {
                                if (strpos($rp->getKey(), "_") === 0) continue;
                                $propertyValues = $subData->getPropertyValues($rp);
                                $record_value = reset($propertyValues);
                                if ($record_value === false) continue;
                                if ($record_value->getDIType() == SMWDataItem::TYPE_WIKIPAGE) {
                                    $enc_prop = $this->serializeWikiPageDataItem($rp, $record_value);
                                    $propertyValuesToAdd[] = $enc_prop;
                                } else {
                                    $enc_prop = $this->serializeDataItem($rp, $record_value);
                                    if (is_null($enc_prop)) {
                                        continue;
                                    }
                                    $propertyValuesToAdd[] = $enc_prop;
                                }
                            }
                        }
                    } else {
                        // handle relation properties
                        $enc_prop = $this->serializeWikiPageDataItem($property, $value);
                        $propertyValuesToAdd[] = $enc_prop;
                    }

                } else {
                    // handle attribute properties
                    $enc_prop = $this->serializeDataItem($property, $value);
                    if (is_null($enc_prop)) {
                        continue;
                    }
                    $propertyValuesToAdd[] = $enc_prop;
                }
            }
        }

        $doc['smwh_properties'] = $propertyValuesToAdd;
    }

    /**
     * Indexes categories. Either as member categories or super-categories
     *
     * @param Title $title
     * @param array $doc
     */
    private function indexCategories(Title $title, array &$doc) {
        $store = smwfGetStore();
        $subject = SMWDIWikiPage::newFromTitle($title);

        $categories = [];
        $properties = $store->getProperties($subject);
        foreach($properties as $property) {
            if ($property->getKey() == "_INST" || $property->getKey() == "_SUBC") {
                $categories = array_merge($categories, $store->getPropertyValues($subject, $property));
            }
        }

        $prop_ignoreasfacet = wfMessage('fs_prop_ignoreasfacet')->text();
        $ignoreAsFacetProp = SMWDIProperty::newFromUserLabel($prop_ignoreasfacet);

        $doc['smwh_directcategories'] = [];
        $doc['smwh_categories'] = [];
        $allParentCategories = [];
        foreach($categories as $category) {
            // do not index if ignored
            $iafValues = $store->getPropertyValues(SMWDIWikiPage::newFromTitle($category->getTitle()), $ignoreAsFacetProp);
            if (count($iafValues) > 0) {
                continue;
            }

            // index this category
            $doc['smwh_directcategories'][] = $category->getTitle()->getDBkey();
            $allParentCategories[] = $category->getTitle();
        }

        // index all categories recursively
        $allCategories = $this->getAllSuperCategories($allParentCategories);
        $allCategories = array_unique($allCategories);
        foreach($allCategories as $pc) {
            $doc['smwh_categories'][] = $pc;
        }
    }

    /**
     * Returns all parent categories, recursively.
     *
     * @param array of Title objects $categories for starting the recursion
     * @return array transitive superclass closure of categories
     */
    private function getAllSuperCategories($categories) {
        $y = [];
        foreach($categories as $category) {
            $y = $this->getAllSuperCategoriesInternal($category, $y);
        }
        return $y;
    }

    /**
     * Returns all parent categories.
     *
     * @param Title $root the current root category
     * @param array $categories temporary list of already found cateagories, for endless-loop protection
     * @return array transitive superclass closure of categories
     */
    private function getAllSuperCategoriesInternal($root, $temp) {
        $y = $temp;
        $y[] = $root->getDBkey();
        $parentCategories = $root->getParentCategories();
        foreach($parentCategories as $parentCategoryName => $childCat) {
            $parentCatTitle = Title::newFromText($parentCategoryName);
            if( ! in_array($parentCatTitle->getDBkey(), $y) ) {
                $y = $this->getAllSuperCategoriesInternal($parentCatTitle, $y);
            }
        }
        return $y;
    }

    private function serializeWikiPageDataItem($property, $dataItem) {

        $title = $dataItem->getTitle();
        $valueId = $title->getPrefixedText();
        $valueLabel = FacetedSearchUtil::findDisplayTitle($title);
        return new PropertyValues(new Property($property->getLabel(), Datatype::WIKIPAGE),
            [ new MWTitle($valueId, $valueLabel) ]);
    }


    private function serializeDataItem($property, $dataItem) {

        $valueXSD = $dataItem->getSerialization();

        $type = $dataItem->getDIType();

        // The values of all attributes are stored according to their type.
        if ($type == SMWDataItem::TYPE_TIME) {

            // Required format: 1995-12-31T23:59:59Z
            $valueXSD = FacetedSearchUtil::getISODateFromDataItem($dataItem);

            return new PropertyValues(new Property($property->getLabel(), Datatype::DATETIME),
                [ $valueXSD ]);

        } else if ($type == SMWDataItem::TYPE_NUMBER) {
            return new PropertyValues(new Property($property->getLabel(), Datatype::NUMBER),
                [ $valueXSD ]);

        } else if ($type == SMWDataItem::TYPE_BOOLEAN) {
            return new PropertyValues(new Property($property->getLabel(), Datatype::BOOLEAN),
                [ $valueXSD ]);

        } else if ($type == SMWDataItem::TYPE_CONCEPT) {
            return null;

        }

        return new PropertyValues(new Property($property->getLabel(), Datatype::STRING),
            [ $valueXSD ]);

        return $propXSD;
    }


}