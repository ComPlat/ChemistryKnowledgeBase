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

        $chemFormId = $chemFormRepo->getChemFormId("reserved-".$moleculeKey);
        if (is_null($chemFormId)) {
            $chemFormId = $chemFormRepo->getChemFormId($moleculeKey);
            if (is_null($chemFormId)) {
                return array('', 'noparse' => true, 'isHTML' => true);
            }
        }

        $hasRGroups = count(MolfileProcessor::getRGroupIds($formula)) > 0;
        $attributes['showrgroups'] = $hasRGroups && !self::isMoleculeOrReaction($wgTitle);

        $attributes['chemFormPage'] = MoleculePageCreationJob::getPageTitleToCreate($chemFormId, $formula);
        $attributes['moleculeKey'] = $moleculeKey;

        $output = self::renderFormulaInContext($attributes);

        return array($output, 'noparse' => true, 'isHTML' => true);
    }

    private static function renderFormulaInContext($attributes): string
    {
        if ($attributes['moleculeKey'] != '' && self::imageNotExists($attributes['moleculeKey']) && !WikiTools::isInVisualEditor()) {
            $output = self::getRenderButton($attributes['moleculeKey'], $attributes['formula']);
        } else {

            $views = __DIR__ . '/../../views';
            $cache = __DIR__ . '/../../cache';
            $blade = new Blade ($views, $cache);

            global $wgScriptPath, $wgTitle;
            $output = $blade->view()->make("molecule",
                [
                    'url' => $attributes['chemFormPage']->getFullURL(),
                    'label' => $attributes['chemFormPage']->getText(),
                    'fullPageTitle' => $attributes['chemFormPage']->getPrefixedText(),
                    'pageId' => $wgTitle->getArticleID(),
                    'moleculeKey' => $attributes['moleculeKey'],
                    'imageURL' => $wgScriptPath . "/rest.php/ChemExtension/v1/chemform?moleculeKey=" . urlencode($attributes['moleculeKey']),
                    'width' => $attributes['width'],
                    'height' => $attributes['height'],
                    'showrgroups' => $attributes['showrgroups']
                ]
            )->render();

        }
        return $output;
    }

    private static function imageNotExists($moleculeKey): bool
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $chemFormRepo = new ChemFormRepository($dbr);
        return $chemFormRepo->getChemFormImageByKey($moleculeKey) == ''
            && $chemFormRepo->getChemFormImageByKey("reserved-".$moleculeKey) == '';
    }

    private static function serializeAttributes(array $attributes): string
    {
        $html = '';
        foreach ($attributes as $key => $value) {
            $value = str_replace('"', '&quot;', $value);
            $html .= " $key='" . $value . "'";
        }
        return $html;
    }

    private static function getRenderButton($chemFormId, $formula): Tag
    {

        OutputPage::setupOOUI();
        self::outputKetcher();

        $label = new LabelWidget([
            'classes' => ['render-formula-note'],
            'label' => 'Image of molecule was not yet rendered',
            'flags' => ['primary', 'progressive'],
            'data' => ['inchikey' => $chemFormId, 'formula' => $formula],
            'infusable' => true
        ]);

        $section = new FormLayout(['items' => [$label]]);
        $div = new Tag('div');
        $div->appendContent($section);
        return $div;

    }

    private static function outputKetcher(): void
    {
        global $wgScriptPath, $wgOut;
        $random = uniqid();
        $path = "$wgScriptPath/extensions/ChemExtension/ketcher/index-editor.html?random=$random";
        $output = sprintf('<iframe style="display: none;" id="ketcher-renderer" src="%s"></iframe>', $path);
        $wgOut->addHTML($output);
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
        $pagesThatUseFormula = $repo->getPageByChemFormId(ChemTools::getChemFormIdFromPageTitle($wgTitle->getPrefixedText()));
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

        $html = $blade->view()->make("molecule-list",
            [
                'topicPages' => $topicPages,
                'publicationPages' => $publicationPages,
                'investigationPages' => $investigationPages,
                'otherPages' => $otherPages
            ]
        )->render();
        $out->addHTML($html);
    }
}
