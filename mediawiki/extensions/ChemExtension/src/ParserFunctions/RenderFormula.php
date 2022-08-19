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
use OutputPage;
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
        $attributes['isreaction'] = $arguments['isreaction'] == '1' || $arguments['isreaction'] == 'true' ? "true" : "false";

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );
        $chemFormRepo = new ChemFormRepository($dbr);
        $moleculeKey = MolfileProcessor::generateMoleculeKey($formula, $arguments['smiles'], $arguments['inchikey']);
        $chemFormId = $chemFormRepo->getChemFormId($moleculeKey);
        $attributes['chemFormId'] = $chemFormId + ChemFormRepository::BASE_ID;

        $attributes['downloadURL'] = urlencode($wgScriptPath . "/rest.php/ChemExtension/v1/chemform?moleculeKey=$moleculeKey");

        $hasRGroups = count(MolfileProcessor::getRestIds($formula)) > 0;
        $attributes['showrgroups'] = $hasRGroups && !self::isMoleculeOrReaction($wgTitle) ? 'true' : 'false' ;

        $queryString = http_build_query([
            'width' => $attributes['width'],
            'height' => $attributes['height'],
            'chemformid' => $attributes['chemFormId'],
            'isreaction' => $attributes['isreaction'],
            'moleculekey' => $moleculeKey,
            'pageid' => is_null($wgTitle) ? '' : $wgTitle->getArticleID(),
            'random' => uniqid()
        ]);
        global $wgScriptPath;
        $attributes['src'] = "$wgScriptPath/extensions/ChemExtension/ketcher/index-formula.html?$queryString";
        $serializedAttributes = self::serializeAttributes($attributes);
        $output = self::renderFormulaInContext($moleculeKey, $formula, $arguments, $serializedAttributes);

        return array($output, 'noparse' => true, 'isHTML' => true);
    }

    private static function renderFormulaInContext($chemFormId, $formula, $arguments, $serializedAttributes): string
    {

        $output = '';
        if (self::isOnMoleculePageAndImageDoesNotExist($chemFormId)) {
            $output .= self::getRenderButton($chemFormId, $formula);
        } else {
            $output .= "<iframe $serializedAttributes></iframe>";
        }

        return $output;
    }

    private static function isOnMoleculePageAndImageDoesNotExist($chemFormId)
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );
        $chemFormRepo = new ChemFormRepository($dbr);

        global $wgTitle;
        if (!self::isMoleculeOrReaction($wgTitle)) {
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
            'data' => ['inchikey' => $chemFormId, 'formula' => $formula],
            'infusable' => true
        ]);

        $section = new FormLayout(['items' => [$label, $saveButton]]);
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
