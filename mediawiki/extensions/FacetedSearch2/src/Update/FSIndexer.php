<?php

namespace DIQA\FacetedSearch2\Update;

use DIQA\FacetedSearch2\ConfigTools;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use Sanitizer;
use SMW\DIProperty as SMWDIProperty;
use SMW\DIWikiPage as SMWDIWikiPage;
use SMW\Services\ServicesFactory as ApplicationFactory;
use WikiPage;

class FSIndexer
{

    public static function indexArticle(Title $title, &$messages = []): void
    {
        self::indexArticlesWithText([$title], null, $messages);
    }

    public static function indexArticles(array $titles, &$messages = []): void
    {
        self::indexArticlesWithText($titles, null, $messages);
    }

    public static function indexArticlesWithText(array $titles, $text, &$messages = []): void
    {
        $client = ConfigTools::getFacetedSearchUpdateClient();
        $smwDBReader = new MWDBReader();
        $documents = [];
        foreach ($titles as $title) {
            $documents[] = $smwDBReader->fromWikiPage(new WikiPage($title), $text, $messages);
        }
        $client->updateDocuments(...$documents);
    }

    public static function indexArticlesWithDependent($title, & $messages = []): void
    {
        $pagesToUpdate = [];
        $pagesToUpdate[] = $title;
        $pagesToUpdate = array_merge($pagesToUpdate, self::retrieveDependent($title));
        $pagesToUpdate = array_unique($pagesToUpdate);

        self::indexArticles($pagesToUpdate, $messages);

    }

    public static function deleteArticleFromIndex($id): void
    {
        $client = ConfigTools::getFacetedSearchUpdateClient();
        $client->deleteDocument($id);
    }

    public static function updateIndexForMovedArticle($oldid, $newid): void
    {
        $client = ConfigTools::getFacetedSearchUpdateClient();
        $client->deleteDocument($oldid);

        // The article with the new name has the same page id as before
        $wp = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromID($newid);

        $content = $wp->getContent(RevisionRecord::RAW);
        if ($content == null) {
            $text = '';
        } else {
            $text = MediaWikiServices::getInstance()->getContentRenderer()->getParserOutput($content, $wp);
            $text = $text->getRawText() ?? '';
            $text = Sanitizer::stripAllTags($text);
        }

        FSIndexer::indexArticlesWithText([$wp->getTitle()], $text);

    }



    private static function retrieveDependent($title): array
    {
        if (!defined('SMW_VERSION')) {
            return [];
        }
        $dependant = [];
        $subject = SMWDIWikiPage::newFromTitle($title);
        $store = ApplicationFactory::getInstance()->getStore();
        $inProperties = $store->getInProperties($subject);

        foreach ($inProperties as $inProperty) {
            /** @var SMWDIProperty $inProperty */
            $subjects = $store->getPropertySubjects($inProperty, $subject);
            foreach ($subjects as $subj) {
                if ($subj->getTitle()->getPrefixedText() !== $title->getPrefixedText()) {
                    $dependant[] = $subj->getTitle();
                }
            }
        }

        // remove duplicate titles. works because of Title::__toString()
        return array_unique($dependant);
    }

}