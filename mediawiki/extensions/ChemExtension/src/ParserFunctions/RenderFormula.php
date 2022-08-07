<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Pages\ChemFormParser;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Utils\ArrayTools;
use DIQA\ChemExtension\Utils\MolfileProcessor;
use DIQA\ChemExtension\Utils\WikiTools;
use MediaWiki\MediaWikiServices;
use OOUI\ButtonWidget;
use OOUI\FormLayout;
use OOUI\LabelWidget;
use OOUI\Tag;
use Parser;
use Philo\Blade\Blade;
use PPFrame;

class RenderFormula {


    public static function renderFormula($formula, array $arguments, Parser $parser, PPFrame $frame): array
    {
        global $wgScriptPath;
        $attributes = [];

        $attributes['class'] = "chemformula";
        $attributes['width'] = $arguments['width'] ?? "300px";
        $attributes['height'] = $arguments['height'] ?? "200px";
        $float = $arguments['float'] ?? 'none';
        if ($float !== 'none') {
            $attributes['style'] = "float: $float;";
        }

        $attributes['smiles'] = base64_encode($arguments['smiles'] ?? '');
        $attributes['formula'] = base64_encode($formula);
        $attributes['isreaction'] = $arguments['isreaction'] == '1' || $arguments['isreaction'] == 'true' ? "true" : "false";

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );
        $chemFormRepo = new ChemFormRepository($dbr);
        $chemFormId = self::generateUniqueId($formula, $arguments);
        $attributes['chemFormId'] = $chemFormRepo->getChemFormId($chemFormId);

        $attributes['downloadURL'] = urlencode($wgScriptPath . "/rest.php/ChemExtension/v1/chemform?id=$chemFormId");

        $queryString = http_build_query([
            'width' => $attributes['width'],
            'height' => $attributes['height'],
            'chemformid' => $attributes['chemFormId'],
            'isreaction' => $attributes['isreaction'],
            'random' => uniqid()
        ]);
        global $wgScriptPath;
        $attributes['src'] = "$wgScriptPath/extensions/ChemExtension/ketcher/index-formula.html?$queryString";
        $serializedAttributes = self::serializeAttributes($attributes);
        $output = self::renderFormulaInContext($chemFormId, $formula, $arguments, $serializedAttributes);

        return array($output, 'noparse' => true, 'isHTML' => true);
    }

    private static function renderFormulaInContext($chemFormId, $formula, $arguments, $serializedAttributes): string
    {

        $output = '';
        if (WikiTools::isInVisualEditor()) {
            $output .= "<iframe $serializedAttributes></iframe>";
        } else {
            if (self::isOnMoleculePageAndImageDoesNotExist($chemFormId)) {
                $output .= self::getRenderButton($chemFormId, $formula);
            } else {
                if (count(MolfileProcessor::getRestIds($formula)) > 0) {
                    $output .= self::getRestTable($chemFormId, $formula, $arguments);
                }
                $output .= "<iframe $serializedAttributes></iframe>";
            }

        }
        return $output;
    }

    private static function isOnMoleculePageAndImageDoesNotExist($chemFormId) {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );
        $chemFormRepo = new ChemFormRepository($dbr);

        global $wgTitle;
        if (is_null($wgTitle) || (
                $wgTitle->getNamespace() !== NS_MOLECULE
                && $wgTitle->getNamespace() !== NS_REACTION
            )) {
            return false;
        }
        return is_null($chemFormRepo->getChemFormImage($chemFormId));

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

        $label = new LabelWidget();
        $label->setLabel('');
        $saveButton = new ButtonWidget([
            'classes' => [],
            'id' => 'render-formula-button',
            'label' => 'render',
            'flags' => ['primary', 'progressive'],
            'data' => [ 'inchikey' => $chemFormId, 'formula' => $formula ],
            'infusable' => true
        ]);

        $section = new FormLayout(['items' => [$label, $saveButton]]);
        $div = new Tag('div');
        $div->appendContent($section);
        return $div;

    }

    private static function getRestTable($chemFormId, $formula, $arguments): string
    {
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new Blade ($views, $cache);

        $restsAsColumns = ChemFormParser::parseRests($arguments);
        $restsAsRows = ArrayTools::transpose($restsAsColumns);
        return $blade->view ()->make ( "show-rests",
            [
                'headers' => array_keys($restsAsColumns),
                'rests' => $restsAsRows,
            ]
        )->render ();

    }

    private static function outputKetcher(): void {
        global $wgScriptPath, $wgOut;
        $random = uniqid();
        $path = "$wgScriptPath/extensions/ChemExtension/ketcher/index-editor.html?random=$random";
        $output = sprintf('<iframe style="display: none;" id="ketcher-renderer" src="%s"></iframe>', $path);
        $wgOut->addHTML($output);
    }

    /**
     * Returns the unique ID for a molecule.
     * For a concrete molecule this is always the inchiKey. For a molecule template this is
     * the smiles string + the rests in sorted order
     *
     * @param string $formula
     * @param array $arguments
     * @return mixed|string
     */
    private static function generateUniqueId(string $formula, array $arguments)
    {
        $key = $arguments['inchikey'];
        if (is_null($key) || $key === '') {

            $key = $arguments['smiles'] . implode('', MolfileProcessor::getRestIds($formula));

        }
        return $key;
    }
}
