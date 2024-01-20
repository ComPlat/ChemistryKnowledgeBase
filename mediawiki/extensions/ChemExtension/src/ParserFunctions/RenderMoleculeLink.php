<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Pages\ChemFormRepository;
use MediaWiki\MediaWikiServices;
use Parser;
use Philo\Blade\Blade;
use SMWDIProperty;
use Title;
use SMWDIWikiPage;

class RenderMoleculeLink
{

    public static function renderMoleculeLink(Parser $parser)
    {
        $parametersAsStringArray = func_get_args();
        array_shift($parametersAsStringArray); // get rid of Parser
        $parameters = ParserFunctionParser::parseArguments($parametersAsStringArray);

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $chemFormRepo = new ChemFormRepository($dbr);
        if (isset($parameters['link']) && $parameters['link'] != '') {
            $chemformId = $chemFormRepo->getChemFormId($parameters['link']);
            if (is_null($chemformId)) {
                return self::returnAsHTML("Molecule with key {$parameters['link']} does not exist.");
            }
        } else if (isset($parameters['chemformid']) && $parameters['chemformid'] != '') {
            $chemformId = $parameters['chemformid'];
            if (is_null($chemformId)) {
                return self::returnAsHTML("Molecule with chemformId {$parameters['chemformid']} does not exist.");
            }
        } else {
            return self::returnAsHTML("-missing link parameter-");
        }

        $moleculeKey = $chemFormRepo->getMoleculeKey($chemformId);
        if (is_null($moleculeKey)) {
            return self::returnAsHTML("Molecule with chemformId {$chemformId} does not exist.");
        }

        $page = Title::newFromText("$chemformId", NS_MOLECULE);
        if (!$page->exists()) {
            $page = Title::newFromText("$chemformId", NS_REACTION);
            if (!$page->exists()) {
                return self::returnAsHTML("Molecule with ID $chemformId does not exist.");
            }
        }


        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new Blade ($views, $cache);


        global $wgScriptPath;
        $html = $blade->view()->make("molecule-link",
            [
                'url' => $page->getFullURL(),
                'label' => $page->getText(),
                'name' => self::getNamesOfMolecule($page),
                'fullPageTitle' => $page->getPrefixedText(),
                'imageURL' => $wgScriptPath . "/rest.php/ChemExtension/v1/chemform?moleculeKey=" . urlencode($moleculeKey),
                'image' => ($parameters['image'] ?? false) === "true",
                'width' => $parameters['width'] ?? 300,
                'height' => $parameters['height'] ?? 200,
            ]
        )->render();


        $html = str_replace("\n", "", $html);
        return self::returnAsHTML($html);
    }

    private static function returnAsHTML($text)
    {
        return [$text, 'noparse' => true, 'isHTML' => true];
    }

    private static function getNamesOfMolecule($moleculeTitle) {
        $moleculeTitleWP = SMWDIWikiPage::newFromTitle($moleculeTitle);
        $res = smwfGetStore()->getPropertyValues($moleculeTitleWP,
            SMWDIProperty::newFromUserLabel("Trivialname"));
        if (count($res) > 0) {
            $first = reset($res);
            return $first->getString();
        } else {
            $moleculeTitleWP = SMWDIWikiPage::newFromTitle($moleculeTitle);
            $res = smwfGetStore()->getPropertyValues($moleculeTitleWP,
                SMWDIProperty::newFromUserLabel("Abbreviation"));
            if (count($res) > 0) {
                $first = reset($res);
                return $first->getString();
            } else {
                $moleculeTitleWP = SMWDIWikiPage::newFromTitle($moleculeTitle);
                $res = smwfGetStore()->getPropertyValues($moleculeTitleWP,
                    SMWDIProperty::newFromUserLabel("IUPACName"));
                if (count($res) > 0) {
                    $first = reset($res);
                    return $first->getString();
                }
            }
        }
        return null;
    }
}