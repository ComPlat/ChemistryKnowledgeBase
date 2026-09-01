<?php

namespace DIQA\FacetedSearch2\ElasticSearch;

use DIQA\FacetedSearch2\Exceptions\BackendException;
use DIQA\FacetedSearch2\Model\Common\Datatype;
use DIQA\FacetedSearch2\Model\Common\Range;
use DIQA\FacetedSearch2\Model\Request\DocumentQuery;
use DIQA\FacetedSearch2\Model\Request\FacetQuery;
use DIQA\FacetedSearch2\Model\Request\StatsQuery;
use DIQA\FacetedSearch2\Model\Response\CategoryFacetCount;
use DIQA\FacetedSearch2\Model\Response\CategoryFacetValue;
use DIQA\FacetedSearch2\Model\Response\Document;
use DIQA\FacetedSearch2\Model\Response\DocumentsResponse;
use DIQA\FacetedSearch2\Model\Response\MWTitleWithURL;
use DIQA\FacetedSearch2\Model\Response\NamespaceFacetCount;
use DIQA\FacetedSearch2\Model\Response\NamespaceFacetValue;
use DIQA\FacetedSearch2\Model\Response\PropertyFacetCount;
use DIQA\FacetedSearch2\Model\Response\PropertyFacetValues;
use DIQA\FacetedSearch2\Model\Response\PropertyValueCount;
use DIQA\FacetedSearch2\Model\Response\PropertyWithURL;
use DIQA\FacetedSearch2\Model\Response\Stats;
use DIQA\FacetedSearch2\Model\Response\ValueCount;
use DIQA\FacetedSearch2\Utils\WikiTools;

class QueryResponseParser {

    /**
     * @throws BackendException
     */
    public function parseDocumentsResponse(array $response, DocumentQuery $q): DocumentsResponse
    {
        $documents = array_map(fn($hit) => $this->parseDocument($hit), $response['hits']['hits']);
        $categoryCounts = array_map(fn($b) => new CategoryFacetCount($b['key'], WikiTools::getDisplayTitleForCategory($b['key']), $b['doc_count']),
            $response['aggregations']['category_frequency']['buckets']);
        $namespaceCounts = array_map(fn($b) => new NamespaceFacetCount($b['key'], WikiTools::getNamespaceName($b['key']), $b['doc_count']),
            $response['aggregations']['namespace_frequency']['buckets']);
        $propertyFacetCounts = $this->parsePropertyCounts($response['aggregations']['field_frequency']['buckets']);

        $numResults = $response['hits']['total']['value'] ?? 0;

        return new DocumentsResponse($numResults,
            $documents,
            $categoryCounts,
            $propertyFacetCounts,
            $namespaceCounts,
            WikiTools::titleExists($q->searchText));
    }

    private function parsePropertyCounts(array $buckets): array
    {
        $counts = [];

        foreach ($buckets as $bucket) {
            $property = Helper::fromInternalName($bucket['key']);
            $counts[] = new PropertyFacetCount(
                PropertyWithURL::fromProperty($property,
                    WikiTools::getDisplayTitleForProperty($property->getTitle()),
                    WikiTools::createURLForProperty($property->getTitle())
                ),
                $bucket['doc_count']);
        }
        return $counts;
    }

    private function parseProperties($properties): array
    {
        $results = [];

        foreach ($properties as $propertyWithType => $values) {
            if (str_starts_with($propertyWithType, '__')) {
                continue;
            }

            $property = Helper::fromInternalName($propertyWithType);

            switch ($property->getType()) {
                case Datatype::STRING:
                case Datatype::NUMBER:
                case Datatype::DATETIME:
                    $facetValues = $values;
                    break;
                case Datatype::BOOLEAN:
                    $facetValues = array_map(fn($v) => $v === 'true', $values);
                    break;
                case Datatype::WIKIPAGE:
                    $facetValues = array_map(fn($v) => new MWTitleWithURL(
                        $v['title'],
                        $v['display'],
                        WikiTools::createURLForPage($v['title'])), $values);
                    break;
                default:
                    throw new BackendException("Unknown property type {$property->getType()} for property {$property->getTitle()}");
            }
            $results[] = new PropertyFacetValues(
                PropertyWithURL::fromProperty($property,
                    WikiTools::getDisplayTitleForProperty($property->getTitle()),
                    WikiTools::createURLForProperty($property->getTitle())),
                $facetValues);
        }
        return $this->fillEmptyExtraProperties($results);
    }

    private function fillEmptyExtraProperties(array $propertyFacetValues): array
    {
        global $fs2gExtraPropertiesToRequest;

        foreach($fs2gExtraPropertiesToRequest as $extraProperty) {
            if (count(array_filter($propertyFacetValues, fn($p) => $p->property->title === $extraProperty->title)) === 0) {
                $displayTitle = WikiTools::getDisplayTitleForProperty($extraProperty->title);
                $propertyWithUrl = PropertyWithURL::fromProperty(
                    $extraProperty,
                    $displayTitle,
                    WikiTools::createURLForProperty($extraProperty->title)
                );
                $propertyFacetValues[] = new PropertyFacetValues($propertyWithUrl, []);
            }
        }
        return $propertyFacetValues;
    }

    /**
     * @throws BackendException
     */
    public function parseDocument($doc): Document
    {
        return new Document($doc['_id'],
            $this->parseProperties($doc['_source']),
            array_map(fn($c) => CategoryFacetValue::fromCategory($c), $doc['_source']['__categories']),
            array_map(fn($c) => CategoryFacetValue::fromCategory($c), $doc['_source']['__directCategories']),
            NamespaceFacetValue::fromNamespace($doc['_source']['__namespace']),
            $doc['_source']['__title'],
            $doc['_source']['__display'],
            WikiTools::createURLForPage($doc['_source']['__title'], $doc['_source']['__namespace']),
            $doc['_score'] ?? 0,
            $doc['highlight']['__fulltext'][0] ?? '');
    }

    public function parsePropertyValueCounts(FacetQuery $q, $aggregations): array
    {
        $propertyValueCounts = [];
        foreach ($q->getPropertyValueQueries() as $pvq) {
            $toInternalName = Helper::toInternalName($pvq->getProperty());
            if ($pvq->getProperty()->getType() === Datatype::WIKIPAGE) {
                $valueCounts = array_map(fn($b) => ValueCount::fromTitle(
                    new MWTitleWithURL($b['key'][0], $b['key'][1], WikiTools::createURLForPage($b['key'][0])),
                    $b['doc_count']),
                    $aggregations[$toInternalName]['values']['buckets']);
            } elseif($pvq->getProperty()->getType() === Datatype::BOOLEAN) {
                $valueCounts = array_map(fn($b) => ValueCount::fromValue($b['key'] ? "true": "false", $b['doc_count']),
                    $aggregations[$toInternalName]['buckets']);
            } else {
                $valueCounts = array_map(fn($b) => ValueCount::fromValue($b['key'], $b['doc_count']),
                    $aggregations[$toInternalName]['buckets']);
            }
            $propertyValueCounts[] = new PropertyValueCount(PropertyWithURL::fromProperty(
                $pvq->getProperty(),
                WikiTools::getDisplayTitleForProperty($pvq->getProperty()->getTitle()),
                WikiTools::createURLForProperty($pvq->getProperty()->getTitle())), $valueCounts);
        }

        foreach ($q->getRangeQueries() as $property) {
            $toInternalName = Helper::toInternalName($property);
            $buckets = $aggregations[$toInternalName]['buckets'] ?? [];
            if (count($buckets) === 0) {
                continue;
            }

            $valueCounts = array_map(function ($b) use ($property) {
                if ($property->getType() === Datatype::DATETIME) {
                    $from = $b['from_as_string'];
                    $to = $b['to_as_string'];

                } else {
                    $from = $b['from'];
                    $to = $b['to'];
                }
                return ValueCount::fromRange(new Range($from, $to), $b['doc_count']);
            }, $buckets);
            $valueCounts = array_values(array_filter($valueCounts, fn($vc) => $vc->count > 0));

            $propertyValueCounts[] = new PropertyValueCount(PropertyWithURL::fromProperty(
                $property,
                WikiTools::getDisplayTitleForProperty($property->getTitle()),
                WikiTools::createURLForProperty($property->getTitle())), $valueCounts);
        }
        return $propertyValueCounts;
    }

    public function parseStats(StatsQuery $q, $aggregations): array
    {
        $stats = [];
        foreach ($q->getStatsProperties() as $property) {
            $toInternalName = Helper::toInternalName($property);
            if ($property->getType() === Datatype::DATETIME) {
                if (!isset($aggregations['min' . $toInternalName]['value_as_string'])) {
                    continue;
                }
                $min = $aggregations['min' . $toInternalName]['value_as_string'];
                $max = $aggregations['max' . $toInternalName]['value_as_string'];

            } else {
                $min = $aggregations['min' . $toInternalName]['value'] ?? 0;
                $max = $aggregations['max' . $toInternalName]['value'] ?? 0;
            }
            $sum = $aggregations['sum' . $toInternalName]['value'] ?? 0;
            $cardinality = $aggregations['cardinality' . $toInternalName]['value'] ?? 0;

            $propertyWithURL = PropertyWithURL::fromProperty($property,
                WikiTools::getDisplayTitleForProperty($property->title),
                WikiTools::createURLForProperty($property->title));
            $stat = new Stats($propertyWithURL,
                $min,
                $max,
                $cardinality,
                $sum
            );
            $stats[] = $stat;
        }
        return $stats;
    }
}