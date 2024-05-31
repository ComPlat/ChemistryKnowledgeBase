<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Jobs\CreateAuthorPageJob;
use DIQA\ChemExtension\Literature\DOIResolver;
use DIQA\ChemExtension\Literature\DOITools;
use DIQA\ChemExtension\Literature\LiteratureRepository;
use Exception;
use MediaWiki\MediaWikiServices;
use Parser;
use Philo\Blade\Blade;

class DOIData
{

    /**
     * Renders DOI data
     *
     * @param Parser $parser
     * @param $doi
     * @param $property
     * @return array
     * @throws Exception
     */
    public static function renderDOIData(Parser $parser): array
    {
        try {
            $parametersAsStringArray = func_get_args();
            array_shift($parametersAsStringArray); // get rid of Parser
            $parameters = ParserFunctionParser::parseArguments($parametersAsStringArray);

            $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
            $repo = new LiteratureRepository($dbr);
            $literature = $repo->getLiterature($parameters['doi']);
            if (is_null($literature)) {
                $doiResolver = new DOIResolver();
                $data = $doiResolver->resolve($parameters['doi']);
            } else {
                $data = $literature['data'];
            }
            $property = trim($parameters['property']);
            switch ($property) {
                case 'author':
                {
                    $authors = DOITools::formatAuthors($data->author);
                    $result = implode(", ", array_map(fn($e) => $e['name'], $authors));
                    break;
                }
                case 'publicationDate':
;                    $result = DOITools::parseDateFromDateParts($data->{'published-print'}->{'date-parts'} ?? '');
                    if (is_null($result)) {
                        $result = DOITools::parseDateFromDateParts($data->{'published-online'}->{'date-parts'} ?? '');
                    }
                    if (is_null($result)) {
                        $result = '';
                    }
                    break;
                case 'publisher':
                    $result = $data->publisher ?? '';
                    break;
                case 'journal':
                    $result = $data->{"container-title"} ?? "";
                    break;

                case 'authorWithOrcid':
                    $authors = DOITools::formatAuthors($data->author);
                    $result = implode("", array_map(function($author) {
                            $orcid = $author['orcidUrl'] != '' ? $author['orcidUrl'] : "-";
                            $authorPage = CreateAuthorPageJob::getAuthorPageTitle($author['name']);
                            return "{{#subobject:|Author={$author['name']}|Orcid={$orcid}|AuthorPage={$authorPage->getPrefixedText()} }}";
                        }, $authors));

                    return [$result, 'noparse' => false, 'isHTML' => false];
                case 'usedBy':
                {
                    $titles = $repo->getPagesForDOI($parameters['doi']);
                    $result = implode(", ",
                        array_map(fn($e) => '<a title="' . $e->getText() . '" href="' . $e->getFullURL() . '">['
                            . DOITools::generateReferenceIndex($data)
                            . ']</a>', $titles));
                    break;
                }
                case 'doilink':
                {
                    global $wgScriptPath;
                    $result = '<a target="_blank" href="'.$wgScriptPath.'/index.php?title=Special:Literature&doi='.
                        urlencode($parameters['doi']).'">Details</a>';
                    break;
                }
                default:
                    $result = 'unknown property: "' . $property . '"';
                    break;
            }

            return [$result, 'noparse' => true, 'isHTML' => false];
        } catch (Exception $e) {
            // fail silently not to pollute annotations
            return ['', 'noparse' => true, 'isHTML' => false];
        }
    }


    /**
     * @throws Exception
     */
    private static function getBlade(): Blade
    {
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        if (!is_writable($cache)) {
            throw new Exception("cache folder for blade engine is not writeable: $cache");
        }
        return new Blade ( $views, $cache );
    }
}
