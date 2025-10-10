<?php

namespace DIQA\FacetedSearch2\SolrClient;

use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use DIQA\FacetedSearch2\Model\Common\Datatype;
use DIQA\FacetedSearch2\Model\Common\Range;
use DIQA\FacetedSearch2\Model\Response\CategoryFacetCount;
use DIQA\FacetedSearch2\Model\Response\CategoryFacetValue;
use DIQA\FacetedSearch2\Model\Response\Document;
use DIQA\FacetedSearch2\Model\Response\DocumentsResponse;
use DIQA\FacetedSearch2\Model\Response\FacetResponse;
use DIQA\FacetedSearch2\Model\Response\MWTitleWithURL;
use DIQA\FacetedSearch2\Model\Response\NamespaceFacetCount;
use DIQA\FacetedSearch2\Model\Response\NamespaceFacetValue;
use DIQA\FacetedSearch2\Model\Response\PropertyFacetCount;
use DIQA\FacetedSearch2\Model\Response\PropertyFacetValues;
use DIQA\FacetedSearch2\Model\Response\PropertyValueCount;
use DIQA\FacetedSearch2\Model\Response\PropertyWithURL;
use DIQA\FacetedSearch2\Model\Response\Stats;
use DIQA\FacetedSearch2\Model\Response\StatsResponse;
use DIQA\FacetedSearch2\Model\Response\ValueCount;
use DIQA\FacetedSearch2\Utils\WikiTools;

class SolrResponseParser {

    private $body;

    /**
     * SolrResponseParser constructor.
     * @param $body
     */
    public function __construct($body)
    {
        $this->body = $body;

    }

    public function parse($fillEmptyProperties = true): DocumentsResponse {
        $docs = [];
        foreach ($this->body->response->docs as $doc) {
            $propertyFacets = []; /* @var PropertyFacetValues[] */
            $categoryFacets = []; /* @var CategoryFacetValue[] */
            $directCategoryFacets = []; /* @var CategoryFacetValue[] */
            $namespace = null;
            foreach ($doc as $property => $value) {
                    if (self::startsWith($property, "smwh_namespace_id")) {
                        $namespace = new NamespaceFacetValue($value, WikiTools::getNamespaceName($value));
                    } else if (self::startsWith($property, "smwh_categories")) {
                        $categoryFacets = array_map(fn($category) => new CategoryFacetValue(
                            $category,
                            WikiTools::getDisplayTitleForCategory($category),
                            WikiTools::createURLForCategory($category)
                        ), $value);

                    } else if (self::startsWith($property, "smwh_directcategories")) {
                        $directCategoryFacets = array_map(fn($category) => new CategoryFacetValue(
                            $category,
                            WikiTools::getDisplayTitleForCategory($category),
                            WikiTools::createURLForCategory($category)
                        ), $value);

                    } else if (self::endsWith($property, "_s") || self::endsWith($property, "_datevalue_l")) {
                        continue;
                    } else if (self::startsWith($property, "smwh_")) {
                        $item = $this->parsePropertyWithValues($property, $value);
                        if (!is_null($item)) {
                            $propertyFacets[] = $item;
                        }
                    }
            }
            $highlighting = null;
            if (isset($this->body->highlighting)) {
                $smwh_search_field = $this->body->highlighting->{$doc->id}->smwh_search_field;
                $highlighting = $smwh_search_field[0];
                if ($highlighting === '' && count($smwh_search_field) > 1) {
                    $highlighting = $smwh_search_field[1];
                }
            }
            if ($fillEmptyProperties) {
                $propertyFacets = $this->fillEmptyExtraProperties($propertyFacets);
            }
            $docs[] = new Document(
                $doc->id,
                $propertyFacets,
                $categoryFacets,
                $directCategoryFacets,
                $namespace,
                $doc->smwh_title,
                $doc->smwh_displaytitle,
                WikiTools::createURLForPage($doc->smwh_title, $namespace->namespace),
                $doc->score ?? 0,
                $highlighting
            );

        }
        $smwh_categories = $this->body->facet_counts->facet_fields->smwh_categories ?? [];
        $categoryFacetCounts = []; /* @var CategoryFacetCount[] */
        foreach ($smwh_categories as $category => $count) {
            $categoryFacetCounts[] = new CategoryFacetCount($category, WikiTools::getDisplayTitleForCategory($category), $count);
        }
        $smwh_properties = $this->body->facet_counts->facet_fields->smwh_properties ?? [];
        $propertyFacetCounts = []; /* @var PropertyFacetCount[] */
        foreach ($smwh_properties as $property => $count) {
            $propertyFacetCount = new PropertyFacetCount($this->parseProperty($property), $count);
            if (!is_null($propertyFacetCount)) {
                $propertyFacetCounts[] = $propertyFacetCount;
            }
        }
        $smwh_attributes = $this->body->facet_counts->facet_fields->smwh_attributes ?? [];
        foreach ($smwh_attributes as $property => $count) {
            $propertyFacetCount = new PropertyFacetCount($this->parseProperty($property), $count);
            if (!is_null($propertyFacetCount)) {
                $propertyFacetCounts[] = $propertyFacetCount;
            }
        }
        $smwh_namespaces = $this->body->facet_counts->facet_fields->smwh_namespace_id ?? [];
        $namespaceFacetCounts = []; /* @var NamespaceFacetCount[] */
        foreach ($smwh_namespaces as $namespace => $count) {
            $namespaceFacetCounts[] = new NamespaceFacetCount($namespace, WikiTools::getNamespaceName($namespace), $count);
        }

        return new DocumentsResponse(
            $this->body->response->numFound,
            $docs,
            $categoryFacetCounts,
            $propertyFacetCounts,
            $namespaceFacetCounts
        );

    }

    public function parseStatsResponse(): StatsResponse {

        $stats = [] /* @var Stats[] */;
        if ( isset($this->body->stats->stats_fields)) {
            foreach($this->body->stats->stats_fields as $p => $info) {
                $property = $this->parsePropertyFromStats($p);
                if (!is_null($property)) {
                    $stat = new Stats($property,
                        $info->min ?? 0,
                        $info->max ?? 0,
                        $info->count ?? 0,
                        $info->sum ?? 0
                    );
                    $stats[] = $stat;
                }
            }
        }

        return new StatsResponse($stats);
    }

    public function parseFacetResponse(): FacetResponse {
        $r = null;
        $propertyValueCount = [] /* @var PropertyValueCount[] */;
        $ranges = [];
        $properties = [];
        foreach ($this->body->facet_counts->facet_queries as $key => $count) {
            if ($count === 0) {
                continue;
            }
            $propertyRange = explode(':', $key);
            $property = $this->parsePropertyFromStats($propertyRange[0]);
            preg_match_all("/\[(.*) TO (.*)\]/", $propertyRange[1], $range);

            if ($property->getType() === Datatype::DATETIME) {
                $from = Carbon::createFromIsoFormat('YYYYMMDDHHmmss', $range[1][0]);
                $to = Carbon::createFromIsoFormat('YYYYMMDDHHmmss', $range[2][0]);
                global $fs2gDateTimeZone;
                if ($fs2gDateTimeZone !== '') {
                    $tz = CarbonTimeZone::create($fs2gDateTimeZone);
                    $offsetFromInHours = $tz->getOffset($from)/3600;
                    $offsetToInHours = $tz->getOffset($to)/3600;
                    $from = $from->addHours($offsetFromInHours);
                    $to = $to->addHours($offsetToInHours);
                }
                $r = new ValueCount(null, null, new Range($from->toIso8601ZuluString(), $to->toIso8601ZuluString()), $count);
            } else if ($property->getType() === Datatype::NUMBER) {
                $r = new ValueCount(null, null, new Range($range[1][0], $range[2][0]), $count);

            } else {
                continue;
            }
            $ranges[$property->title][] = $r;
            $properties[$property->title] = $property;
        }
        foreach ($properties as $key => $property) {
            $propertyValueCount[] = new PropertyValueCount($property, $ranges[$key]);
        }

        foreach ($this->body->facet_counts->facet_fields as $p => $values) {
            if ($p === 'smwh_categories' || $p === 'smwh_attributes' || $p === 'smwh_properties' || $p === 'smwh_namespace_id') continue;
            $property = $this->parseProperty($p);
            if ($property->getType() === Datatype::DATETIME || $property->getType() === Datatype::NUMBER) {
                continue;
            }
            $valueCounts = [] /* @var ValueCount[] */;
            foreach($values as $v => $count) {
                if ($property->getType() === Datatype::WIKIPAGE) {
                    list($title, $displayTitle) = explode("|", $v);
                    $valueCounts[] = new ValueCount(null, new MWTitleWithURL($title, $displayTitle, WikiTools::createURLForPage($title)), null, $count);
                } else {
                    $valueCounts[] = new ValueCount($v, null, null, $count);
                }
            }
            $propertyValueCount[] = new PropertyValueCount($property, $valueCounts);
        }
        return new FacetResponse($propertyValueCount);

    }

    private function parsePropertyWithValues(string $property, $values): ?PropertyFacetValues {
        list($name, $type) = Helper::parseSOLRProperty($property);
        if (is_null($name)) return null;
        $displayTitle = WikiTools::getDisplayTitleForProperty($name);
        if ($type === Datatype::WIKIPAGE) {
            return new PropertyFacetValues(
                new PropertyWithURL($name, $displayTitle,
                    Datatype::WIKIPAGE,
                    WikiTools::createURLForProperty($name)
                ),
                array_map(function($e) {
                    $parts = explode("|", $e);
                    return new MWTitleWithURL($parts[0], $parts[1], WikiTools::createURLForPage($parts[0]));
                }, $values));
        } else {
            global $fs2gDateTimeZone;
            if ($type === Datatype::DATETIME) {
                if ($fs2gDateTimeZone !== '') {
                    $values = array_map(function ($v) use ($fs2gDateTimeZone) {
                        $datetime = Carbon::createFromIsoFormat("YYYY-MM-DDTHH:mm:ssZ", $v);
                        $tz = CarbonTimeZone::create($fs2gDateTimeZone);
                        $offsetInHours = $tz->getOffset($datetime)/3600;
                        return $datetime->addHours($offsetInHours)->toIso8601ZuluString();
                    }, $values);
                }
            }
            return new PropertyFacetValues(
                new PropertyWithURL($name, $displayTitle,
                    $type,
                    WikiTools::createURLForProperty($name)
                ),
                $values);
        }

    }

    private function parseProperty(string $property): ?PropertyWithURL {
        list($name, $type) = Helper::parseSOLRProperty($property);
        if (is_null($name)) {
            return null;
        }
        $displayTitle = WikiTools::getDisplayTitleForProperty($name);
        if ($type === Datatype::WIKIPAGE) {
            return new PropertyWithURL($name, $displayTitle, Datatype::WIKIPAGE, WikiTools::createURLForProperty($name));
        } else {
            return new PropertyWithURL($name, $displayTitle, $type, WikiTools::createURLForProperty($name));
        }

    }

    private function parsePropertyFromStats(string $property): ?PropertyWithURL {
        list($name, $type) = Helper::parseSOLRProperty($property);
        if (is_null($name)) {
            return null;
        }
        $displayTitle = WikiTools::getDisplayTitleForProperty($name);
        return new PropertyWithURL($name, $displayTitle, $type, WikiTools::createURLForProperty($name));
    }

    private static function startsWith($string, $query){
        return substr($string, 0, strlen($query)) === $query;
    }

    private static function endsWith( $haystack, $needle ) {
        $length = strlen( $needle );
        if( !$length ) {
            return true;
        }
        return substr( $haystack, -$length ) === $needle;
    }

    private function fillEmptyExtraProperties(array $propertyFacetValues)
    {
        global $fs2gExtraPropertiesToRequest;

        foreach($fs2gExtraPropertiesToRequest as $extraProperty) {
            if (count(array_filter($propertyFacetValues, fn($p) => $p->property->title === $extraProperty->title)) === 0) {
                $displayTitle = WikiTools::getDisplayTitleForProperty($extraProperty->title);
                $propertyWithUrl = new PropertyWithURL(
                    $extraProperty->title,
                    $displayTitle,
                    $extraProperty->type,
                    WikiTools::createURLForProperty($extraProperty->title)
                );
                $propertyFacetValues[] = new PropertyFacetValues($propertyWithUrl, []);
            }
        }
        return $propertyFacetValues;
    }
}
