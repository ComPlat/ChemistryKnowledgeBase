<?php

namespace DIQA\ChemExtension;

use CommentStoreComment;
use DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculesImportJob;
use DIQA\ChemExtension\Pages\ChemForm;
use DIQA\ChemExtension\Pages\ChemFormParser;
use DIQA\ChemExtension\Pages\PageCreator;
use DIQA\ChemExtension\Utils\LoggerUtils;
use MediaWiki\Revision\RenderedRevision;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\User\UserIdentity;
use Status;
use JobQueueGroup;

class MultiContentSave
{


    public static function onMultiContentSave(RenderedRevision $renderedRevision,
                                              UserIdentity $user, CommentStoreComment $summary, $flags,
                                              Status $hookStatus)
    {
        $logger = new LoggerUtils('AfterDataUpdateCompleteHandler', 'ChemExtension');

        $revision = $renderedRevision->getRevision();
        if ($revision == null || $revision->getContent(SlotRecord::MAIN) == null) {
            // something is wrong. there is no revision
            return;
        }
        $wikitext = $revision->getContent(SlotRecord::MAIN)->getWikitextForTransclusion();#

        $chemFormParser = new ChemFormParser();
        $chemForms = $chemFormParser->parse($wikitext);
        $pageCreator = new PageCreator();
        $moleculeCollections = [];
        foreach ($chemForms as $chemForm) {
            try {
                $title = $pageCreator->createNewMoleculePage($chemForm);
                if (count($chemForm->getRests()) > 0) {
                    $moleculeCollections[] = ['title' => $title, 'chemForm' => $chemForm];
                    $logger->log("Created molecule collection page: {$title->getPrefixedText()}, smiles: {$chemForm->getSmiles()}");
                } else {
                    $logger->log("Created molecule/reaction page: {$title->getPrefixedText()}, smiles: {$chemForm->getSmiles()}");
                }
            } catch (\Exception $e) {
                $logger->error($e->getMessage());
            }
        }

        if (count($moleculeCollections) > 0) {
            self::addMoleculeCollectionJob($moleculeCollections);
        }
    }

    /**
     * @param ChemForm $chemForm
     * @param Title|null $title
     */
    private static function addMoleculeCollectionJob(array $moleculeCollections): void
    {
        global $wgTitle;

        $jobParams = [];
        $jobParams['moleculeCollections'] = $moleculeCollections;
        $job = new MoleculesImportJob($wgTitle, $jobParams);
        JobQueueGroup::singleton()->push($job);

    }
}
