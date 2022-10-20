<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Literature\DOITools;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Utils\WikiTools;
use MediaWiki\MediaWikiServices;
use Parser;
use Philo\Blade\Blade;
use Title;

class RenderMoleculeLink
{

    public static function renderMoleculeLink(Parser $parser)
    {
        $parametersAsStringArray = func_get_args();
        array_shift($parametersAsStringArray); // get rid of Parser
        $parameters = ParserFunctionParser::parseArguments($parametersAsStringArray);

        if (isset($parameters['link'])) {
            $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
            $chemFormRepo = new ChemFormRepository($dbr);
            $chemformId = $chemFormRepo->getChemFormId($parameters['link']);
        } else if (isset($parameters['chemformid'])) {
            $chemformId = $parameters['chemformid'];
        } else {
            return ["-missing link parameter-", 'noparse' => true, 'isHTML' => true];
        }

        if (is_null($chemformId)) {
            return ["Molecule with ID $chemformId does not exist.", 'noparse' => true, 'isHTML' => true];
        }

        $page = Title::newFromText("Molecule_$chemformId", NS_MOLECULE);
        if (!$page->exists()) {
            $page = Title::newFromText("Collection_$chemformId", NS_MOLECULE);
            if (!$page->exists()) {
                $page = Title::newFromText("Reaction_$chemformId", NS_REACTION);
                if (!$page->exists()) {
                    return ["Molecule with ID $chemformId does not exist.", 'noparse' => true, 'isHTML' => true];
                }
            }
        }

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new Blade ($views, $cache);

        if (WikiTools::isInVisualEditor()) {

            $html = $page->getText();

        } else {

            $html = $blade->view()->make("molecule-link",
                [
                    'url' => $page->getFullURL(),
                    'label' => $page->getText()
                ]
            )->render();

        }

        $html = str_replace("\n", "", $html);
        return [$html, 'noparse' => true, 'isHTML' => true];
    }

}