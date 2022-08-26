<?php

namespace DIQA\ChemExtension\Literature;

use DIQA\ChemExtension\ParserFunctions\RenderLiterature;
use Philo\Blade\Blade;
use OutputPage;

class DOIRenderer {

    public function render($doiData) {
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
        global $wgScriptPath;

        $html = $blade->view ()->make ( "doi-rendered",
            [
                'index' => DOITools::generateReferenceIndex($doiData),
                'title'  => strip_tags($doiData->title,"<sub><sup><b><i>"),
                'authors' => $authors,
                'journal' => $journal,
                'volume' => $volume,
                'pages' => $pages,
                'year' => $year,
                'doi' => $doiData->DOI,
                'wgScriptPath' => $wgScriptPath,
            ]
        )->render ();

        return str_replace("\n", "", $html);
    }

    public function renderReference($doiData) {
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new Blade ( $views, $cache );

        $html = $blade->view ()->make ( "doi-reference",
            [
                'index' => DOITools::generateReferenceIndex($doiData),
            ]
        )->render ();

        return str_replace("\n", "", $html);
    }

    public static function outputLiteratureReferences(OutputPage $out): void {
        if (count(RenderLiterature::$LITERATURE_REFS) === 0) {
            return;
        }
        $out->addHTML("<h2>Literature</h2>");
        $doiRenderer = new self();
        foreach (RenderLiterature::$LITERATURE_REFS as $l) {
            $output = $doiRenderer->render($l['data']);
            $out->addHTML($output);
        }
    }
}