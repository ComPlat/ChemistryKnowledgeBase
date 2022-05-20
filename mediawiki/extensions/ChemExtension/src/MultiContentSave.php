<?php

namespace DIQA\ChemExtension;

use CommentStoreComment;
use DIQA\ChemExtension\Pages\ChemFormParser;
use DIQA\ChemExtension\Pages\PageCreator;
use DIQA\ChemExtension\Utils\LoggerUtils;
use MediaWiki\Revision\RenderedRevision;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\User\UserIdentity;
use Status;

class MultiContentSave
{


    public static function onMultiContentSave( RenderedRevision $renderedRevision,
                                               UserIdentity $user, CommentStoreComment $summary, $flags,
                                               Status $hookStatus )
    {
        $logger = new LoggerUtils('AfterDataUpdateCompleteHandler', 'ChemExtension');

        $revision = $renderedRevision->getRevision();
        if( $revision == null || $revision->getContent( SlotRecord::MAIN ) == null ) {
            // something is wrong. there is no revision
            return;
        }
        $wikitext = $revision->getContent( SlotRecord::MAIN )->getWikitextForTransclusion();#

        $chemFormParser = new ChemFormParser();
        $chemForms = $chemFormParser->parse($wikitext);
        $pageCreator = new PageCreator();
        foreach($chemForms as $chemForm) {
            try {
                $title = $pageCreator->createNewMoleculePage($chemForm);
                $logger->log("Created molecule/reaction page: {$title->getPrefixedText()}, smiles: {$chemForm->getSmiles()}");
            } catch(Exception $e) {
                $logger->error($e->getMessage());
            }
        }
    }


}
