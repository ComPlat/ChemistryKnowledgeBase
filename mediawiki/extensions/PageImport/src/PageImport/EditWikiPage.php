<?php
namespace DIQA\PageImport;

use CommentStoreComment;
use ContentHandler;
use Exception;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use RecentChange;
use RequestContext;
use Title;
use User;
use WikiPage;

/**
 *  Helper class to update the contents of a wiki page.
 */
class EditWikiPage {

    public const UPDATED = 1;
    public const CREATED = 2;
    public const NOT_UPDATED = 3;
    public const ERROR = 4;

    /**
     * Replaces the content of the given page with the provided newContentsText.
     * If the old and the new text are equal, no update will be done.
     *
     * @param String|Title $page            title of the wiki page that should be changed
     * @param String       $newContentsText the new wiki text that should be used
     * @param String       $editMessageText the text that should be used as the comment of the edit
     * @param int $flags
     *      EDIT_NEW
     *          Article is known or assumed to be non-existent, create a new one
     *      EDIT_UPDATE
     *          Article is known or assumed to be pre-existing, update it
     *      EDIT_MINOR
     *          Mark this edit minor, if the user is allowed to do so
     *      EDIT_SUPPRESS_RC
     *          Do not log the change in recentchanges
     *      EDIT_FORCE_BOT
     *          Mark the edit a "bot" edit regardless of user rights
     *      EDIT_DEFER_UPDATES
     *          Defer some of the updates until the end of index.php
     *      EDIT_AUTOSUMMARY
     *          Fill in blank summaries with generated text where possible
     * @param User     $user the user who performs the change
     * @return boolean whether or not the update has succeeded
     */
    public static function doEditContent( $title, $newContentsText, $editMessageText, $flags=EDIT_UPDATE | EDIT_MINOR, $user=null) {
        if( $user==null ) {
            $user = RequestContext::getMain()->getUser();
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

        if( self::equalIgnoringLineFeeds(trim($newContent->getWikitextForTransclusion()), trim( $oldText )) ) {
            // do nothing
            return self::NOT_UPDATED;

        } else {
            $comment = CommentStoreComment::newUnsavedComment( $editMessageText );

            $updater = (new WikiPage( $title ))->newPageUpdater( $user );
            $updater->setContent( SlotRecord::MAIN, $newContent );
            $updater->setRcPatrolStatus( RecentChange::PRC_PATROLLED );
            $updater->saveRevision( $comment , $flags );

            if ( $updater->wasSuccessful() && $oldText=='' ) {
                return self::CREATED;
            } else if ( $updater->wasSuccessful() ) {
                return self::UPDATED;
            } else {
                return self::ERROR;
            }

        }
    }

    public static function equalIgnoringLineFeeds($a, $b) {
        $a = str_replace(["\r", "\n"], "\n", $a);
        $b = str_replace(["\r", "\n"], "\n", $b);
        $a = preg_replace("/\n+/", "\n", $a);
        $b = preg_replace("/\n+/", "\n", $b);
        return strcmp($a, $b) === 0;
    }

    /**
     * Replaces the content of the given page with the provide newContentsText.
     * If the old and the new text are equal, no update will be done.
     *
     * @param String|Title $title           title of the wiki page that should be changed
     * @param String       $editMessageText the text that should be used as the comment of the edit
     *
     * @return boolean whether or not the update has succeeded
     */
    public static function doNullEdit( $title, $editMessageText ) {
        if( ! $title instanceof Title ) {
            $title = Title::newFromDBkey(str_replace(' ', '_', $title));
        }

        if(! $title->exists()) {
            $errorMessage= "Cannot modify page '$title'. It does not exist.";
            throw new Exception($errorMessage);
        }

        $revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionByTitle( $title );
        $oldContent = $revision->getContent( SlotRecord::MAIN )->getWikitextForTransclusion();
        if($oldContent == null) {
            $oldContent = '';
        }

        $newContent = ContentHandler::makeContent( $oldContent, $title );
        $comment = CommentStoreComment::newUnsavedComment( $editMessageText );

        $updater = (new WikiPage( $title ))->newPageUpdater( RequestContext::getMain()->getUser() );
        $updater->setContent( SlotRecord::MAIN, $newContent );
        $updater->setRcPatrolStatus( RecentChange::PRC_PATROLLED );
        $updater->saveRevision( $comment , EDIT_UPDATE | EDIT_MINOR | EDIT_SUPPRESS_RC | EDIT_FORCE_BOT );

        if ($updater->wasSuccessful()) {
            return self::UPDATED;
        } else {
            return self::ERROR;
        }
    }

}
