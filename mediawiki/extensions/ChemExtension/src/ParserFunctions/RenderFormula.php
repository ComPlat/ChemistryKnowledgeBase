<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\NavigationBar\NavigationBar;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Pages\MoleculePageCreationJob;
use DIQA\ChemExtension\Utils\ChemTools;
use DIQA\ChemExtension\Utils\MolfileProcessor;
use DIQA\ChemExtension\Utils\WikiTools;
use MediaWiki\MediaWikiServices;
use OOUI\FormLayout;
use OOUI\LabelWidget;
use OOUI\Tag;
use OutputPage;
use Parser;
use Philo\Blade\Blade;
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
        $attributes['showrgroups'] = $hasRGroups && !self::isMoleculeOrReaction($wgTitle);

        $attributes['chemFormPage'] = MoleculePageCreationJob::getPageTitleToCreate($chemFormId, $formula);
        $attributes['moleculeKey'] = $moleculeKey;

        $output = self::renderFormulaInContext($attributes);

        return array($output, 'noparse' => true, 'isHTML' => true);
    }

    private static function renderFormulaInContext($attributes): string
    {

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new Blade ($views, $cache);

        global $wgScriptPath, $wgTitle;
        return $blade->view()->make("molecule",
            [
                'url' => $attributes['chemFormPage']->getFullURL(),
                'label' => $attributes['chemFormPage']->getText(),
                'fullPageTitle' => $attributes['chemFormPage']->getPrefixedText(),
                'pageId' => is_null($wgTitle) ? '' : $wgTitle->getArticleID(),
                'moleculeKey' => $attributes['moleculeKey'],
                'imageURL' => $wgScriptPath . "/rest.php/ChemExtension/v1/chemform?moleculeKey=" . urlencode($attributes['moleculeKey']),
                'width' => $attributes['width'],
                'height' => $attributes['height'],
                'showrgroups' => $attributes['showrgroups'],
                'placeHolderImg' => "$wgScriptPath/extensions/ChemExtension/skins/images/formula-placeholder.png",
                'imageAlreadyRendered' => $attributes['imageAlreadyRendered']
            ]
        )->render();
    }

    private static function renderEmptyMolecule($attributes): string
    {

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new Blade ($views, $cache);

        global $wgScriptPath, $wgTitle;
        return $blade->view()->make("molecule",
            [
                'moleculeKey' => '',
                'width' => $attributes['width'],
                'height' => $attributes['height'],
                'placeHolderImg' => "$wgScriptPath/extensions/ChemExtension/skins/images/formula-placeholder.png"
            ]
        )->render();
    }

    /**
     * @param \Title $wgTitle
     * @return bool
     */
    private static function isMoleculeOrReaction(?Title $wgTitle): bool
    {
        return !is_null($wgTitle) && (
                $wgTitle->getNamespace() === NS_MOLECULE
                || $wgTitle->getNamespace() === NS_REACTION
            );
    }

    public static function outputMoleculeReferences(OutputPage $out): void
    {
        global $wgTitle;
        if (!self::isMoleculeOrReaction($wgTitle)) {
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
        $blade = new Blade ($views, $cache);

        $publicationPageForConcreteMolecule = $repo->getPublicationPageForConcreteMolecule($wgTitle);

        $html = $blade->view()->make("molecule-list",
            [
                'topicPages' => $topicPages,
                'publicationPages' => $publicationPages,
                'investigationPages' => $investigationPages,
                'otherPages' => $otherPages,
                'publicationPageForConcreteMolecule' => $publicationPageForConcreteMolecule,
            ]
        )->render();
        $out->addHTML($html);

    }
}
