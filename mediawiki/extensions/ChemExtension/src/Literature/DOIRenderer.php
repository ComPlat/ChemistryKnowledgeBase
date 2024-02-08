<?php

namespace DIQA\ChemExtension\Literature;

use DIQA\ChemExtension\ParserFunctions\RenderLiterature;
use DIQA\ChemExtension\Utils\ArrayTools;
use MediaWiki\MediaWikiServices;
use Philo\Blade\Blade;
use OutputPage;

class DOIRenderer
{

    private static $PUBLICATIONS_FOUND = [];

    public function renderReference($doiData)
    {
        if ($doiData === '__placeholder__') {
            return '<div class="chem_ext_literature">reference will be resolved</div>';
        }
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new Blade ($views, $cache);

        $authors = array_map(function ($e) {
            return "{$e->given} {$e->family}";
        }, $doiData->author);

        $year = $doiData->issued->{"date-parts"}[0][0] ?? "";
        $journal = $doiData->{"container-title"} ?? "";
        $volume = $doiData->volume ?? "";
        $pages = $doiData->page ?? "";
        global $wgScriptPath;

        $html = $blade->view()->make("doi-rendered",
            [
                'index' => DOITools::generateReferenceIndex($doiData),
                'title' => strip_tags(ArrayTools::getFirstIfArray($doiData->title), "<sub><sup><b><i>"),
                'authors' => $authors,
                'journal' => $journal,
                'volume' => $volume,
                'pages' => $pages,
                'year' => $year,
                'doi' => $doiData->DOI,
                'wgScriptPath' => $wgScriptPath,
            ]
        )->render();

        return str_replace("\n", "", $html);
    }

    public function renderDOIInfoTemplate($data): string
    {
        if (is_null($data)) return '';

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new Blade ($views, $cache);

        $wikitext = "{{DoiInfo\n";

        $parameters = $blade->view()->make("doi-infobox",
            [
                'doi' => $data->DOI,
                'type' => DOITools::getTypeLabel($data->type),
                'title' => strip_tags(ArrayTools::getFirstIfArray($data->title), "<sub><sup><b><i>"),
                'authors' => DOITools::formatAuthors($data->author),
                'submittedAt' => date('d.m.Y', ($data->created->timestamp / 1000)),
                'publishedOnlineAt' => DOITools::parseDateFromDateParts($data->{'published-online'}->{'date-parts'} ?? ''),
                'publishedPrintAt' => DOITools::parseDateFromDateParts($data->{'published-print'}->{'date-parts'} ?? ''),
                'publisher' => $data->publisher ?? '-',
                'licenses' => DOITools::formatLicenses($data->license ?? ''),
                'issue' => $data->issue ?? '-',
                'year' => $doiData->issued->{"date-parts"}[0][0] ?? "-",
                'journal' => $doiData->{"container-title"} ?? "-",
                'volume' => $data->volume ?? '-',
                'pages' => $data->page ?? '-',
                'subjects' => $data->subject ?? [],
                'funders' => count($data->funder ?? []) === 0 ? [] : array_map(function ($e) {
                    return $e->name;
                }, $data->funder),
            ]
        )->render();
        $wikitext .= trim($parameters);
        $wikitext .= "\n}}";
        return $wikitext;
    }

    public function renderReferenceInText($doiData)
    {
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new Blade ($views, $cache);

        $html = $blade->view()->make("doi-reference",
            [
                'index' => DOITools::generateReferenceIndex($doiData),
            ]
        )->render();

        return str_replace("\n", "", $html);
    }


    public static function collectPublications($pageTitle, & $doiData)
    {
        if (!array_key_exists($pageTitle->getPrefixedText(), self::$PUBLICATIONS_FOUND)) {

            $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
            $repo = new LiteratureRepository($dbr);
            $doi = DOITools::getDOIFromPage($pageTitle);
            if (is_null($doi)) {
                return;
            }
            $l = $repo->getLiterature($doi);
            if (is_null($l)) {
                return;
            }
            RenderLiterature::$LITERATURE_REFS[$doi] = $l;
            self::$PUBLICATIONS_FOUND[$pageTitle->getPrefixedText()] = $l;
        }
        $doiData = self::$PUBLICATIONS_FOUND[$pageTitle->getPrefixedText()];
    }

    public static function outputLiteratureReferences(OutputPage $out): void
    {
        if (count(RenderLiterature::$LITERATURE_REFS) === 0) {
            return;
        }
        $out->addHTML("<h2>Literature</h2>");
        $doiRenderer = new self();
        foreach (RenderLiterature::$LITERATURE_REFS as $l) {
            $output = $doiRenderer->renderReference($l['data']);
            $out->addHTML($output);
        }
    }
}