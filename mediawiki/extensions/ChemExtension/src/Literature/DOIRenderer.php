<?php

namespace DIQA\ChemExtension\Literature;

use Philo\Blade\Blade;

class DOIRenderer {

    public function render($doiData) {
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new Blade ( $views, $cache );

        $authors = array_map(function($e) {
            return "{$e->given} {$e->family}";
        }, $doiData->author);

        $year = $doiData->issued->{"date-parts"}[0][0] ?? "";

        $html = $blade->view ()->make ( "doi-rendered",
            [
                'title'  => $doiData->title,
                'authors' => $authors,
                'year' => $year
            ]
        )->render ();

        return str_replace("\n", "", $html);
    }
}