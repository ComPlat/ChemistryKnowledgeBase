<?php

namespace DIQA\ChemExtension\Experiments;

use DIQA\ChemExtension\Utils\QueryUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use Philo\Blade\Blade;
use OutputPage;

class MoleculeRoleDetector {


    private static function getRoleMatrix($chemFormId) {
        $matrix = [];
        $experimentRepo = ExperimentRepository::getInstance();
        foreach($experimentRepo->getAll() as $type => $exp) {
            $roles = $exp['roleProperties'];
            $matrix[$type] = [];
            foreach($roles as $role) {
                $query = <<<QUERY
[[$role::Molecule:$chemFormId]]
[[-Has subobject::<q>[[Category:$type]]</q>]]
QUERY;
                $results = QueryUtils::executeBasicQuery($query);
                $searchResults = [];
                while ($row = $results->getNext()) {

                    $column = reset($row);
                    $dataItem = $column->getNextDataItem();
                    if ($dataItem === false) continue;
                    $searchResults[] = $dataItem->getTitle();
                }
                $count = $results->getCount();
                if ($count > 0) {
                    usort($searchResults, fn($e1, $e2) => strcmp($e1->getSubpageText(), $e2->getSubpageText()));
                    $matrix[$type][$role] = array_unique($searchResults);
                }
            }

        }
        return $matrix;
    }

    public static function renderMatrix(OutputPage $out) {

        global $wgTitle, $wgScriptPath;
        if (!WikiTools::isMoleculeOrReaction($wgTitle)) {
            return;
        }
        $chemFormId = $wgTitle->getText();

        $matrix = self::getRoleMatrix($chemFormId);
        $m = [];
        foreach($matrix as $roles) {
            $m = array_merge(array_keys($roles), $m);
        }
        $distinctRoles = array_unique($m);

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new Blade ($views, $cache);
        $html = $blade->view ()->make ( "molecule-role-matrix",
            [
                'distinctRoles' => $distinctRoles,
                'matrix' => $matrix,
                'wgScriptPath' => $wgScriptPath
            ]
        )->render ();
        $out->addHTML($html);
    }
}
