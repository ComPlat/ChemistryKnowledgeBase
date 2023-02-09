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
        global $wgScriptPath, $wgTitle;
        $attributes = [];

        $attributes['class'] = "chemformula";
        $attributes['width'] = $arguments['width'] ?? "300px";
        $attributes['height'] = $arguments['height'] ?? "200px";
        $float = $arguments['float'] ?? 'none';

        $attributes['style'] = "float: $float;";


        $attributes['smiles'] = base64_encode($arguments['smiles'] ?? '');
        $attributes['formula'] = base64_encode($formula);

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );
        $chemFormRepo = new ChemFormRepository($dbr);
        $moleculeKey = MolfileProcessor::generateMoleculeKey($formula, $arguments['smiles'] ?? '', $arguments['inchikey'] ?? '');

        $chemFormId = $chemFormRepo->addChemForm($moleculeKey);
        $attributes['chemFormId'] = $chemFormId;

        $attributes['downloadURL'] = $wgScriptPath . "/rest.php/ChemExtension/v1/chemform?moleculeKey=" . urlencode($moleculeKey);

        $hasRGroups = count(MolfileProcessor::getRGroupIds($formula)) > 0;
        $attributes['showrgroups'] = $hasRGroups && !self::isMoleculeOrReaction($wgTitle) ? 'true' : 'false';

        $chemFormPage = MoleculePageCreationJob::getPageTitleToCreate($chemFormId, $formula);
        $attributes['chemFormPageText'] = !is_null($chemFormPage) ? $chemFormPage->getText() : '';

        $queryString = http_build_query([
            'width' => $attributes['width'],
            'height' => $attributes['height'],
            'moleculekey' => $moleculeKey,
            'pageid' => is_null($wgTitle) ? '' : $wgTitle->getArticleID(),
            'chemformpage' => !is_null($chemFormPage) ? $chemFormPage->getPrefixedDBkey() : '',
            'random' => uniqid()
        ]);
        global $wgScriptPath;
        $attributes['src'] = "$wgScriptPath/extensions/ChemExtension/ketcher/index-formula.html?$queryString";
        $serializedAttributes = self::serializeAttributes($attributes);
        $output = self::renderFormulaInContext($moleculeKey, $formula, $arguments, $serializedAttributes);

        return array($output, 'noparse' => true, 'isHTML' => true);
    }

    private static function renderFormulaInContext($moleculeKey, $formula, $arguments, $serializedAttributes): string
    {
        if ($moleculeKey != '' && self::imageNotExists($moleculeKey) && !WikiTools::isInVisualEditor()) {
            $output = self::getRenderButton($moleculeKey, $formula);
        } else {
            $output = "<iframe $serializedAttributes></iframe>";
        }
        return $output;
    }

    private static function imageNotExists($moleculeKey): bool
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $chemFormRepo = new ChemFormRepository($dbr);
        return $chemFormRepo->getChemFormImage($moleculeKey) == '';
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
        $pagesThatUseFormula = $repo->getPageFromChemFormIndex(ChemTools::getChemFormIdFromPageTitle($wgTitle->getPrefixedText()));
        if (count($pagesThatUseFormula) === 0) {
            return;
        }

        $topicPages = array_filter($pagesThatUseFormula, function (Title $title) {
            $b = new NavigationBar($title);
            return $title->getNamespace() === NS_CATEGORY && $b->checkIfInTopicCategory($title);
        });
        $publicationPages = array_filter($pagesThatUseFormula, function (Title $title) {
            $b = new NavigationBar($title);
            return $title->getNamespace() === NS_MAIN && !$title->isSubpage() && $b->checkIfInTopicCategory($title);
        });
        $investigationPages = array_filter($pagesThatUseFormula, function (Title $title) {
            $b = new NavigationBar($title);
            return $title->getNamespace() === NS_MAIN && $title->isSubpage() && $b->checkIfInTopicCategory($title->getBaseTitle());
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
