<?php

namespace DIQA\ChemExtension;

use CommentStoreComment;
use DIQA\ChemExtension\Literature\DOITools;
use DIQA\ChemExtension\Literature\LiteraturePageCreator;

use DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculesImportJob;
use DIQA\ChemExtension\Pages\ChemForm;
use DIQA\ChemExtension\Pages\ChemFormParser;
use DIQA\ChemExtension\Pages\MoleculePageCreator;
use DIQA\ChemExtension\ParserFunctions\ParserFunctionParser;
use DIQA\ChemExtension\ParserFunctions\RenderLiterature;
use DIQA\ChemExtension\Utils\LoggerUtils;
use MediaWiki\Revision\RenderedRevision;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\User\UserIdentity;
use Status;
use JobQueueGroup;
use Exception;

class MultiContentSave
{


    public static function onMultiContentSave(RenderedRevision $renderedRevision,
                                              UserIdentity $user, CommentStoreComment $summary, $flags,
                                              Status $hookStatus)
    {

        $revision = $renderedRevision->getRevision();
        if ($revision == null || $revision->getContent(SlotRecord::MAIN) == null) {
            // something is wrong. there is no revision
            return;
        }
        $wikitext = $revision->getContent(SlotRecord::MAIN)->getWikitextForTransclusion();#

        self::parseChemicalFormulas($wikitext);
        self::parseParserFunctions($wikitext);
    }

    private static function parseParserFunctions($wikitext)
    {
        $logger = new LoggerUtils('AfterDataUpdateCompleteHandler', 'ChemExtension');
        $parser = new ParserFunctionParser();
        $creator = new LiteraturePageCreator();
        $literatureFunctions = $parser->parseFunction('literature', $wikitext);

        foreach ($literatureFunctions as $f) {
            $doiAsText = $f['doi'] ?? null;
            $doi = DOITools::parseDOI($doiAsText);
            if (is_null($doi)) {
                continue;
            }

            try {
                $doiData = RenderLiterature::resolveDOI($doi);
                $creator->createPage($doi, $doiData);
            } catch (Exception $e) {
                $logger->error($e->getMessage());
            }
        }
    }

    /**
     * @param $wikitext
     */
    private static function parseChemicalFormulas($wikitext): void
    {
        $logger = new LoggerUtils('AfterDataUpdateCompleteHandler', 'ChemExtension');
        $chemFormParser = new ChemFormParser();
        $chemForms = $chemFormParser->parse($wikitext);

        $pageCreator = new MoleculePageCreator();
        $moleculeCollections = [];
        foreach ($chemForms as $chemForm) {
            try {
                $title = $pageCreator->createNewMoleculePage($chemForm);
                if ($chemForm->hasRGroupDefinitions()) {
                    $moleculeCollections[] = ['title' => $title, 'chemForm' => $chemForm];
                }
            } catch (Exception $e) {
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
