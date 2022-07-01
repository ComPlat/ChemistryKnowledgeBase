<?php

namespace DIQA\ChemExtension\Literature;

use Philo\Blade\Blade;

class DOIRenderer {

    public function render($doiData, $index) {
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new Blade ( $views, $cache );

        $authors = array_map(function($e) {
            return "{$e->given} {$e->family}";
        }, $doiData->author);

        $year = $doiData->issued->{"date-parts"}[0][0] ?? "";
        $journal = $doiData->{"container-title"} ?? "";
        $volume = $doiData->volume ?? "";
        $pages = $doiData->page ?? "";

        $html = $blade->view ()->make ( "doi-rendered",
            [
                'index' => $index,
                'title'  => strip_tags($doiData->title,"<sub><sup><b><i>"),
                'authors' => $authors,
                'journal' => $journal,
                'volume' => $volume,
                'pages' => $pages,
                'year' => $year,
                'doi' => $doiData->DOI
            ]
        )->render ();

        return str_replace("\n", "", $html);
    }

    public function renderReference($index) {
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new Blade ( $views, $cache );

        $html = $blade->view ()->make ( "doi-reference",
            [
                'index' => $index,
            ]
        )->render ();

        return str_replace("\n", "", $html);
    }
}