<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Utils\ArrayTools;
use MediaWiki\MediaWikiServices;
use Parser;
use Philo\Blade\Blade;
use Title;

class ShowMoleculeCollection {

    public static function renderMoleculeCollectionTable(Parser $parser, $param1)
    {
        global $wgTitle;

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );

        $chemFormRepo = new ChemFormRepository($dbr);

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new Blade ( $views, $cache );

        $molecules = $chemFormRepo->getConcreteMolecules($wgTitle);

        $rests = count($molecules) > 0 ? array_keys(ArrayTools::propertiesToArray(json_decode($molecules[0]['rests']))) : [];
        sort($rests);

        $moleculesToDisplay = [];
        foreach($molecules as $m) {
            if (array_key_exists($m['molecule_id'], $moleculesToDisplay)) {
                $moleculesToDisplay[$m['molecule_id']]['publications'][] = $m['publication_id'];
            } else {
                $moleculesToDisplay[$m['molecule_id']] = [ 'publications' => [$m['publication_id']], 'rests' => $m['rests']];
            }
        }

        $rows = [];
        foreach($moleculesToDisplay as $moleculeId => $data) {
            $rows[] = [
                'molecule' => Title::newFromID($moleculeId),
                'publications' => array_map(function($e) { return Title::newFromID($e); }, $data['publications']),
                'rests' => ArrayTools::propertiesToArray(json_decode($data['rests']))
            ];

        }
        $html = $blade->view ()->make ( "molecule-collection-table",
            [
                'rests' => $rests,
                'rows' => $rows,
            ]
        )->render ();

        return [str_replace("\n", "", $html), 'noparse' => true, 'isHTML' => true];
    }
}
