<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Pages\MoleculePageCreator;
use DIQA\ChemExtension\Utils\MolfileProcessor;
use MediaWiki\MediaWikiServices;
use OOUI\ButtonWidget;
use OOUI\FormLayout;
use OOUI\LabelWidget;
use OOUI\Tag;
use OutputPage;
use Parser;
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
        $moleculeKey = MolfileProcessor::generateMoleculeKey($formula, $arguments['smiles'], $arguments['inchikey']);
        $chemFormId = $chemFormRepo->addChemForm($moleculeKey);
        $attributes['chemFormId'] = $chemFormId;

        $attributes['downloadURL'] = urlencode($wgScriptPath . "/rest.php/ChemExtension/v1/chemform?moleculeKey=$moleculeKey");

        $hasRGroups = count(MolfileProcessor::getRGroupIds($formula)) > 0;
        $attributes['showrgroups'] = $hasRGroups && !self::isMoleculeOrReaction($wgTitle) ? 'true' : 'false' ;

        $chemFormPage = MoleculePageCreator::getPageTitleToCreate($chemFormId, $formula);
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

        $output = '';
        if (self::isOnMoleculePageAndImageDoesNotExist($moleculeKey)) {
            $output .= self::getRenderButton($moleculeKey, $formula);
        } else {
            $output .= "<iframe $serializedAttributes></iframe>";
        }

        return $output;
    }

    private static function isOnMoleculePageAndImageDoesNotExist($moleculeKey)
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
}
