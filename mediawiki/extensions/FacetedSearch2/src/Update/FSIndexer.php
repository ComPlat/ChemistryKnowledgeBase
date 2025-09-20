<?php

namespace DIQA\FacetedSearch2\Update;

use DIQA\FacetedSearch2\ConfigTools;
use DIQA\FacetedSearch2\Setup;
use Title;
use WikiPage;
use MediaWiki\MediaWikiServices;
use Sanitizer;

class FSIndexer
{

    public static function indexArticle(Title $title, &$messages = [])
    {
        self::indexArticleWithText($title, null, $messages);
    }

    public static function indexArticleWithText(Title $title, $text, &$messages = [])
    {
        $client = ConfigTools::getFacetedSearchUpdateClient();
        $smwDBReader = new SMWDBReader();
        $document = $smwDBReader->getIndexDocumentFromWikiPage(new WikiPage($title), $text, $messages);
        $client->updateDocument($document);
    }

    public static function deleteArticleFromIndex($id) {
        $client = ConfigTools::getFacetedSearchUpdateClient();
        $client->deleteDocument($id);
    }

    public static function updateIndexForMovedArticle($oldid, $newid)
    {
        $client = ConfigTools::getFacetedSearchUpdateClient();
        $client->deleteDocument($oldid);

        // The article with the new name has the same page id as before
        $wp = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromID($oldid);

        $content = $wp->getContent(RevisionRecord::RAW);
        if ($content == null) {
            $text = '';
        } else {
            $text = MediaWikiServices::getInstance()->getContentRenderer()->getParserOutput($content, $wp);
            $text = $text->getText() ?? '';
            $text = Sanitizer::stripAllTags($text);
        }

        try {
            FSIndexer::indexArticleWithText($wp->getTitle(), $text);
        } catch (Exception $e) {
            // TODO error logging
        }

        return false;
    }
}