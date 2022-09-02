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

class WikiTools {

    public static function isInVisualEditor()
    {
        global $wgRequest;
        return (strpos($wgRequest->getText('title'), '/v3/page/html/') !== false
            || strpos($wgRequest->getText('title'), '/v3/transform/wikitext/to/html/') !== false)
            || $wgRequest->getText('veaction') == 'edit';
    }

    public static function doEditContent( $title, $newContentsText, $editMessageText, $flags=EDIT_UPDATE | EDIT_MINOR, $user=null) {


        if( $user==null ) {
            global $wgUser;
            $user = $wgUser;
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

        if( trim($newContent->getWikitextForTransclusion()) == trim( $oldText ) ) {
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
}
