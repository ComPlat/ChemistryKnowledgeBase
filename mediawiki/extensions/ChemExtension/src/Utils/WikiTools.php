<?php

namespace DIQA\ChemExtension\Utils;

use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use ContentHandler;
use Title;
use CommentStoreComment;
use WikiPage;
use RecentChange;
use EmailNotification;
use User;
use Parser;

class WikiTools {

    public static function sanitizeHTML($html) {
        if (self::isInVisualEditor()) {
            $html = str_replace(array("<tbody>", "</tbody>"), "", $html);
            $html = strip_tags($html, '<table><tr><th><td><span>');
        }
        return str_replace("\n", "", $html);
    }

    public static function isInVisualEditor(): bool
    {
        global $wgRequest;
        return (strpos($wgRequest->getText('title'), '/v3/page/html/') !== false
            || strpos($wgRequest->getText('title'), '/v3/transform/wikitext/to/html/') !== false)
            || $wgRequest->getText('action') == 'visualeditor'
            || $wgRequest->getText('veaction') == 'edit';
    }

    /**
     * Returns the current title. Works also in VisualEditor during REST-calls.
     *
     * @return Title|null
     */
    public static function getCurrentTitle(Parser $parser): ?Title
    {
        global $wgRequest;
        $titleParam = $wgRequest->getVal('page');
        if (!is_null($titleParam) && $titleParam !== '') {
            return Title::newFromText($titleParam);
        }
        global $wgTitle;
        return is_null($wgTitle) ? $parser->getTitle() : $wgTitle;

    }

    public static function doEditContent( $title, $newContentsText, $editMessageText, $flags=EDIT_UPDATE | EDIT_MINOR, $user=null, $force = false) {


        if( $user==null ) {
            $user = \RequestContext::getMain()->getUser();
        }

        if( ! $title instanceof Title ) {
            $title = Title::newFromDBkey(str_replace(' ', '_', $title));
        }

        $revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionByTitle( $title );
        if( $revision == null || $revision->getContent( SlotRecord::MAIN ) == null ) {
            $oldText = '';
        } else {
            $oldText = $revision->getContent( SlotRecord::MAIN )->getWikitextForTransclusion();
        }

        $newContent = ContentHandler::makeContent( $newContentsText, $title );

        if( !$force && trim($newContent->getWikitextForTransclusion()) == trim( $oldText ) ) {
            // do nothing
            return true;

        } else {

            $comment = CommentStoreComment::newUnsavedComment( $editMessageText );

            $updater = WikiPage::factory( $title )->newPageUpdater( $user );
            $updater->setContent( SlotRecord::MAIN, $newContent );
            $updater->setRcPatrolStatus( RecentChange::PRC_PATROLLED );
            $updater->saveRevision( $comment , $flags );
            return $updater->wasSuccessful();
        }
    }

    public static function isMoleculeOrReaction(?Title $wgTitle): bool
    {
        return !is_null($wgTitle) && (
                $wgTitle->getNamespace() === NS_MOLECULE
                || $wgTitle->getNamespace() === NS_REACTION
            );
    }

    public static function getText($title) {

        if( ! $title instanceof Title ) {
            $title = Title::newFromDBkey(str_replace(' ', '_', $title));
        }

        $revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionByTitle( $title );
        if( $revision == null || $revision->getContent( SlotRecord::MAIN ) == null ) {
            $text = '';
        } else {
            $text = $revision->getContent( SlotRecord::MAIN )->getWikitextForTransclusion();
        }
        return $text;
    }

    public static function createNotificationJobs($title)
    {
        $wikiSysop = User::newFromName("WikiSysop");
        $emailNotification = new EmailNotification();
        return $emailNotification->notifyOnPageChange(
            $wikiSysop,
            $title,
            wfTimestampNow(),
            "Die Seite wurde vom ChemScanner importiert",
            false
        );
    }

    public static function checkIfInTopicCategory(Title $title): bool
    {
        static $CHECK_TOPIC_CATEGORY = [];
        if (array_key_exists($title->getPrefixedText(), $CHECK_TOPIC_CATEGORY)) {
            return $CHECK_TOPIC_CATEGORY[$title->getPrefixedText()];
        }
        $categories = [];
        self::getReversedCategoryList($title->getParentCategoryTree(), $categories);
        $CHECK_TOPIC_CATEGORY[$title->getPrefixedText()] = in_array('Topic', array_map(function ($e) { return $e->getText(); }, $categories));
        return $CHECK_TOPIC_CATEGORY[$title->getPrefixedText()];
    }

    public static function getReversedCategoryList($categories, &$allCategories)
    {
        foreach ($categories as $name => $super) {
            if (is_array($super) && count($super) > 0) {
                self::getReversedCategoryList($super, $allCategories);
            }
            $allCategories[] = Title::newFromText($name);
        }
    }

    public static function getInvestigationCategoriesAsString($title) {
        $parentCategories = $title->getParentCategories();
        if (array_key_exists("Category:Investigation", $parentCategories)) {
            unset($parentCategories["Category:Investigation"]);
            return implode(', ', array_map(function ($t) {
                return Title::newFromText($t)->getText();
            }, array_keys($parentCategories)));
        }
        return "";
    }

    public static function sortPageListBySubpages(array & $pageTitles) {
        usort($pageTitles, function($p1, $p2) {
            if ($p1->isSubpage() && !$p2->isSubpage()) {
                return -1;
            } else if (!$p1->isSubpage() && $p2->isSubpage()) {
                return 1;
            } else return 0;
        });
    }
}
