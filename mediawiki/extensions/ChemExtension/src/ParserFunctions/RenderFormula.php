<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Jobs\MoleculePageCreationJob;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Utils\ChemTools;
use DIQA\ChemExtension\Utils\MolfileProcessor;
use DIQA\ChemExtension\Utils\WikiTools;
use MediaWiki\MediaWikiServices;
use OutputPage;
use Parser;
use eftec\bladeone\BladeOne;
use PPFrame;
use Title;

class RenderFormula
{


    public static function renderFormula($formula, array $arguments, Parser $parser, PPFrame $frame): array
    {
        global $wgTitle;
        $attributes = [];

        $attributes['width'] = $arguments['width'] ?? "300px";
        $attributes['height'] = $arguments['height'] ?? "200px";
        $attributes['smiles'] = $arguments['smiles'] ?? '';
        $attributes['margin'] = $arguments['margin'] ?? '0px 0px 0px 0px';
        $attributes['formula'] = $formula;

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );
        $chemFormRepo = new ChemFormRepository($dbr);
        $moleculeKey = MolfileProcessor::generateMoleculeKey($formula, $arguments['smiles'] ?? '', $arguments['inchikey'] ?? '');

        $chemFormId = $chemFormRepo->getChemFormId("reserved-" . $moleculeKey);
        if (is_null($chemFormId)) {
            $chemFormId = $chemFormRepo->getChemFormId($moleculeKey);
            if (is_null($chemFormId)) {
                $output = self::renderEmptyMolecule($attributes);
                return array($output, 'noparse' => true, 'isHTML' => true);
            }
        }

        $imageAlreadyRendered = $chemFormRepo->hasChemFormImageById($chemFormId);
        $attributes['imageAlreadyRendered'] = $imageAlreadyRendered;

        $hasRGroups = count(MolfileProcessor::getRGroupIds($formula)) > 0;
        $attributes['showrgroups'] = $hasRGroups && !WikiTools::isMoleculeOrReaction($wgTitle);

        $attributes['chemFormPage'] = MoleculePageCreationJob::getPageTitleToCreate($chemFormId, $formula);
        $attributes['moleculeKey'] = $moleculeKey;

        $output = self::renderFormulaInContext($attributes);

        return array($output, 'noparse' => true, 'isHTML' => true);
    }

    private static function renderFormulaInContext($attributes): string
    {

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new BladeOne ($views, $cache);

        global $wgScriptPath, $wgTitle;
        $namesOfMolecule = ChemTools::getNamesOfMolecule($attributes['chemFormPage']);
        if (WikiTools::isInVisualEditor()) {
            $namesOfMolecule .= " ({$attributes['chemFormPage']->getText()})";
        }
        return $blade->run("molecule",
            [
                'url' => $attributes['chemFormPage']->getFullURL(),
                'label' => $attributes['chemFormPage']->getText(),
                'fullPageTitle' => $attributes['chemFormPage']->getPrefixedText(),
                'pageId' => is_null($wgTitle) ? '' : $wgTitle->getArticleID(),
                'moleculeKey' => $attributes['moleculeKey'],
                'imageURL' => $wgScriptPath . "/rest.php/ChemExtension/v1/chemform?moleculeKey=" . urlencode($attributes['moleculeKey']),
                'width' => $attributes['width'],
                'height' => $attributes['height'],
                'margin' => $attributes['margin'],
                'showrgroups' => $attributes['showrgroups'],
                'placeHolderImg' => "$wgScriptPath/extensions/ChemExtension/skins/images/formula-placeholder.png",
                'imageAlreadyRendered' => $attributes['imageAlreadyRendered'],
                'name' => $namesOfMolecule,
                'molOrRxn' => $attributes['formula']
            ]
        );
    }

    private static function renderEmptyMolecule($attributes): string
    {

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new BladeOne ($views, $cache);

        global $wgScriptPath, $wgTitle;
        return $blade->run("molecule",
            [
                'moleculeKey' => '',
                'width' => $attributes['width'],
                'height' => $attributes['height'],
                'margin' => $attributes['margin'],
                'placeHolderImg' => "$wgScriptPath/extensions/ChemExtension/skins/images/formula-placeholder.png"
            ]
        );
    }

    public static function outputMoleculeReferences(OutputPage $out): void
    {
        global $wgTitle;
        if (!WikiTools::isMoleculeOrReaction($wgTitle)) {
            return;
        }

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $repo = new ChemFormRepository($dbr);
        $pagesThatUseFormula = $repo->getPagesByChemFormId(ChemTools::getChemFormIdFromPageTitle($wgTitle->getPrefixedText()));
        if (count($pagesThatUseFormula) === 0) {
            return;
        }

        $topicPages = array_filter($pagesThatUseFormula, function (Title $title) {
            return $title->getNamespace() === NS_CATEGORY && WikiTools::checkIfInTopicCategory($title);
        });
        $publicationPages = array_filter($pagesThatUseFormula, function (Title $title) {
            return $title->getNamespace() === NS_MAIN && !$title->isSubpage() && WikiTools::checkIfInTopicCategory($title);
        });
        $investigationPages = array_filter($pagesThatUseFormula, function (Title $title) {
            return $title->getNamespace() === NS_MAIN && $title->isSubpage() && WikiTools::checkIfInTopicCategory($title->getBaseTitle());
        });

        $otherPages = array_udiff($pagesThatUseFormula, array_merge($topicPages, $publicationPages, $investigationPages),
            function (Title $title1, Title $title2) {
                return strcmp($title1->getDBkey(), $title2->getDBkey());
            });

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new BladeOne ($views, $cache);

        $publicationPageForConcreteMolecule = $repo->getPublicationPageForConcreteMolecule($wgTitle);

        $html = $blade->run("molecule-list",
            [
                'topicPages' => $topicPages,
                'publicationPages' => $publicationPages,
                'investigationPages' => $investigationPages,
                'otherPages' => $otherPages,
                'publicationPageForConcreteMolecule' => $publicationPageForConcreteMolecule,
            ]
        );
        $out->addHTML($html);

    }
}
