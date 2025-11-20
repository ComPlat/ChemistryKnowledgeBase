<?php
namespace DIQA\FacetedSearch2\Update;

use Exception;
use ForeignTitle;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use Parser;
use SMW\SemanticData;
use SMW\Store;
use StatusValue;
use WikiPage;

class FSIncrementalUpdater  {

    private function __construct() {
    }

    /**
     * Called when semantic data is refreshed.
     *
     * @param Store $store
     * @param SemanticData $semanticData
     * @return bool
     */
    public static function onUpdateDataAfter(Store $store, SemanticData $semanticData) {
        $wikiTitle = $semanticData->getSubject()->getTitle();
        if (self::shouldCreateUpdateJob()) {
            self::createUpdateJob( $wikiTitle);
        } else {
            FSIndexer::indexArticlesWithDependent($wikiTitle);
        }
        return true;
    }

    /**
     * Called when the article is saved. Not necessary if the namespace of an article may contain semantic links.
     *
     * @param WikiPage $wikiPage
     * @param UserIdentity $user
     * @param string $summary
     * @param int $flags
     * @param RevisionRecord $revisionRecord
     * @param EditResult $editResult
     * @return bool
     */
    public static function onPageSaveComplete( WikiPage $wikiPage, UserIdentity $user, string $summary,
                                               int $flags, RevisionRecord $revisionRecord, EditResult $editResult ) {
        $wikiTitle = $wikiPage->getTitle();

        global $smwgNamespacesWithSemanticLinks;
        if (isset($smwgNamespacesWithSemanticLinks[$wikiTitle->getNamespace()]) &&
            $smwgNamespacesWithSemanticLinks[$wikiTitle->getNamespace()] === true) {
            return true; // already updated in onUpdateDataAfter
        }
        if (self::shouldCreateUpdateJob()) {
            self::createUpdateJob( $wikiTitle);
        } else {
            FSIndexer::indexArticlesWithDependent($wikiTitle);
        }
        return true;

    }

    /**
     * Called when image upload is complete.
     *
     * @param $image
     * @return bool
     */
    public static function onUploadComplete( &$image ) {
        try {

            FSIndexer::indexArticle($image->getLocalFile()->getTitle());

        } catch(Exception $e) {
            wfDebugLog("FacetedSearch2", "Could not update article in Index. Reason: ".$e->getMessage());
        }
        return true;
    }

    /**
     * This function is called after an article was imported via
     * Special:Import.
     * It starts an update of the index for the given title.
     *
     * @param Title $title
     * 		Title under which the revisions were imported
     * @param ForeignTitle $origTitle
     *		Title provided by the XML file
     * @param int $revCount
     *		Number of revisions in the XML file
     * @param int $sRevCount
     *		Number of sucessfully imported revisions
     * @param array $pageInfo
     *		associative array of page information
     * @return bool
     */
    public static function onAfterImportPage(Title $title, ForeignTitle $origTitle, $revCount, $sRevCount, $pageInfo) {
        try {
            FSIndexer::indexArticle($title);

        } catch(Exception $e) {
            wfDebugLog("FacetedSearch2", "Could not update article on import operation in Index. Reason: ".$e->getMessage());
        }
        return true;
    }

    /**
     * This function is called after an article was moved.
     * It starts an update of the index for the given article.
     *
     * @param LinkTarget $old Old title
     * @param LinkTarget $new New title
     * @param UserIdentity $user User who did the move
     * @param int $pageid Database ID of the page that's been moved
     * @param int $redirid Database ID of the created redirect
     * @param string $reason Reason for the move
     * @param RevisionRecord $revision RevisionRecord created by the move
     * @return bool|void True or no return value to continue or false stop other hook handlers,
     *     doesn't abort the move itself
     */

    public static function onPageMoveCompleting( $old, $new, $user, $pageid, $redirid, $reason, $revision ) {
        try {
            FSIndexer::updateIndexForMovedArticle($old, $new);
        } catch(Exception $e) {
            wfDebugLog("FacetedSearch2", "Could not move article in Index. Reason: ".$e->getMessage());
        }
        return true;
    }

    /**
     * This hook is called before a page is deleted.
     *
     * @param ProperPageIdentity $page Page being deleted.
     * @param Authority $deleter Who is deleting the page
     * @param string $reason Reason the page is being deleted
     * @param StatusValue $status Add any error here
     * @param bool $suppress Whether this is a suppression deletion or not
     * @return bool|void True or no return value to continue; false to abort, which also requires adding
     *                   a fatal error to $status.
     */
    public static function onPageDelete(
        ProperPageIdentity $page,
        Authority $deleter,
        string $reason,
        StatusValue $status,
        bool $suppress ) {

        try {
            FSIndexer::deleteArticleFromIndex($page->getID());

        } catch(Exception $e) {
            wfDebugLog("FacetedSearch2", "Could not delete article in Index. Reason: ".$e->getMessage());
        }
        return true;
    }

    /**
     * This method called when a revision is approved.
     * Only if the ApprovedRev extension is installed.
     *
     * @param Parser $parser
     * @param Title $title
     * @param int $rev_id
     * @return bool
     */
    public static function onRevisionApproved(Parser $parser, $title, $rev_id): bool {
        $store = MediaWikiServices::getInstance()->getRevisionStore();
        $revision = $store->getRevisionByTitle( $title, $rev_id );
        if (is_null($revision)) {
            return true;
        }

        $content = $revision->getContent(SlotRecord::MAIN, RevisionRecord::RAW)->serialize();
        try {

            FSIndexer::indexArticlesWithText([$title], $content);
        } catch(Exception $e) {
            wfDebugLog("FacetedSearch2", "Could not update article in Index. Reason: ".$e->getMessage());
        }
        return true;
    }

    private static function createUpdateJob(Title $title ) : void {
        $params = [];
        $params['title'] = $title;
        $job = new UpdateIndexJob($title, $params);
        MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup()->push( $job );
    }

    private static function shouldCreateUpdateJob(): bool
    {
        global $fs2gCreateUpdateJob;
        if (isset($fs2gCreateUpdateJob) && $fs2gCreateUpdateJob === false) {
            return false;
        } else {
            return true;
        }
    }
}