<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Literature\DOITools;
use DIQA\ChemExtension\Utils\WikiTools;
use Parser;
use Philo\Blade\Blade;
use Title;

class RenderMoleculeLink {

    public static function renderMoleculeLink(Parser $parser, $param1, $chemformIdParam = '')
    {
        $parts = explode('=', $chemformIdParam);
        $chemformId = trim($parts[1]);

        $page = Title::newFromText("Molecule_$chemformId", NS_MOLECULE);
        if (!$page->exists()) {
            $page = Title::newFromText("Collection_$chemformId", NS_MOLECULE);
            if (!$page->exists()) {
                $page = Title::newFromText("Reaction_$chemformId", NS_REACTION);
            }
        }

        if (!$page->exists()) {
            return ["Molecule with ID $chemformId does not exist.", 'noparse' => true, 'isHTML' => true];
        }

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new Blade ( $views, $cache );

        if (WikiTools::isInVisualEditor()) {
            $html = "<span>[" . $page->getText() . "]</span>";
        } else {

            $html = $blade->view ()->make ( "molecule-link",
                [
                    'url' => $page->getFullURL(),
                    'label' => $page->getText()
                ]
            )->render ();

        }

        $html = str_replace("\n", "", $html);
        return [$html, 'noparse' => true, 'isHTML' => true];
    }

}