<?php

namespace DIQA\FacetedSearch2\SolrClient;

use DIQA\FacetedSearch2\FacetedSearchClient;
use DIQA\FacetedSearch2\Model\Common\Datatype;
use DIQA\FacetedSearch2\Model\Common\Order;
use DIQA\FacetedSearch2\Model\Common\Property;
use DIQA\FacetedSearch2\Model\Common\Range;
use DIQA\FacetedSearch2\Model\Request\DocumentQuery;
use DIQA\FacetedSearch2\Model\Request\FacetQuery;
use DIQA\FacetedSearch2\Model\Request\PropertyFacet;
use DIQA\FacetedSearch2\Model\Request\PropertyValueQuery;
use DIQA\FacetedSearch2\Model\Request\Sort;
use DIQA\FacetedSearch2\Model\Request\StatsQuery;
use DIQA\FacetedSearch2\Model\Response\CategoryFacetCount;
use DIQA\FacetedSearch2\Model\Response\Document;
use DIQA\FacetedSearch2\Model\Response\DocumentsResponse;
use DIQA\FacetedSearch2\Model\Response\FacetResponse;
use DIQA\FacetedSearch2\Model\Response\StatsResponse;
use DIQA\FacetedSearch2\Utils\WikiTools;
use Exception;
use MediaWiki\MediaWikiServices;
use Title;

class SolrRequestClient implements FacetedSearchClient
{

    public function requestDocument(string $id): Document
    {
        $response = new SolrResponseParser($this->requestSOLR(['q' => "id:$id"]));
        $documentsResponse = $response->parse(false);
        if (count($documentsResponse->docs) === 0) {
            throw new Exception("No document with ID $id found", 400);
        }
        return $documentsResponse->docs[0];
    }

    public function requestDocuments(DocumentQuery $q): DocumentsResponse
    {
        $queryParams = $this->getParams($q->searchText, $q->propertyFacets, $q->categoryFacets,
            $q->namespaceFacets, $q->extraProperties);
        $sortsAndLimits = $this->encodeSortsAndLimits($q->sorts, $q->limit, $q->offset);
        $queryParams = array_merge($queryParams, $sortsAndLimits);

        $response = new SolrResponseParser($this->requestSOLR($queryParams));
        $docResponse = $response->parse()
            ->setDebugInfo(Util::buildQueryParams($queryParams));

        $this->fillEmptyCategoryFacetCounts($docResponse, $q);
        return $docResponse;
    }

    public function requestStats(StatsQuery $q): StatsResponse
    {
        $queryParams = $this->getParams($q->searchText, $q->propertyFacets, $q->categoryFacets,
            $q->namespaceFacets, []);
        $queryParams['stats'] = 'true';
        $statsFields = [];
        foreach ($q->statsProperties as $p) {
            $statsFields[] = Helper::generateSOLRPropertyForSearch($p->title, $p->type);
        }
        $queryParams['stats.field'] = $statsFields;
        $response = new SolrResponseParser($this->requestSOLR($queryParams));
        return $response->parseStatsResponse()
            ->setDebugInfo(Util::buildQueryParams($queryParams));
    }

    public function requestFacets(FacetQuery $q): FacetResponse
    {
        $queryParams = $this->getParams($q->searchText, $q->propertyFacets, $q->categoryFacets,
            $q->namespaceFacets, []);

        $facetPropertiesWithoutConstraints = array_filter($q->getPropertyValueQueries(), fn($e) => !$e->hasConstraints());

        foreach ($facetPropertiesWithoutConstraints as $v) {
            /* @var $v PropertyValueQuery */
            $queryParams['facet.field'][] = Helper::generateSOLRPropertyForSearch($v->getProperty()->getTitle(), $v->getProperty()->getType());
        }

        $statsQuery = new StatsQuery();
        $statsQuery->updateQuery($q);
        $statsQuery->setStatsProperties($q->getRangeQueries());
        $statsResponse = $this->requestStats($statsQuery);

        $facetQueries = [];
        foreach ($statsResponse->getStats() as $stat) {
            $property = Helper::generateSOLRPropertyForSearch($stat->property->title, $stat->property->type);
            foreach($stat->clusters as $range) {
                $encodedRange = self::encodeRange($range, $stat->property->type);
                $facetQueries[] = $property . ":[" . $encodedRange . "]";
            }
        }
        $queryParams['facet.query'] = $facetQueries;
        $response = new SolrResponseParser($this->requestSOLR($queryParams));
        $result = $response->parseFacetResponse();

        $facetPropertiesWithConstraints = array_filter($q->getPropertyValueQueries(), fn($e) => $e->hasConstraints());
        foreach ($facetPropertiesWithConstraints as $v) {
            $singleQueryParams = $this->getParams($q->searchText, $q->propertyFacets, $q->categoryFacets,
                $q->namespaceFacets, []);
            /* @var $v PropertyValueQuery */
            $singleQueryParams['facet.field'] = [Helper::generateSOLRPropertyForSearch($v->getProperty()->getTitle(),
                $v->getProperty()->type)];
            if (!is_null($v->getValueContains())) {
                $singleQueryParams['facet.contains'] = $v->getValueContains();
                $singleQueryParams['facet.contains.ignoreCase'] = 'true';
                $singleQueryParams['facet.sort'] = 'count';
            }
            if (!is_null($v->getValueLimit())) {
                $singleQueryParams['facet.limit'] = $v->getValueLimit();
            }
            if (!is_null($v->getValueOffset())) {
                $singleQueryParams['facet.offset'] = $v->getValueOffset();
            }
            $parser = new SolrResponseParser($this->requestSOLR($singleQueryParams));
            $result->merge($parser->parseFacetResponse());

        }

        return $result->setDebugInfo(Util::buildQueryParams($queryParams));;
    }

    /**
     * Sends a document to Tika and extracts text. If Tika does not
     * know the format, an empty string is returned.
     *
     *  - PDF
     *  - DOC/X (Microsoft Word)
     *  - PPT/X (Microsoft Powerpoint)
     *  - XLS/X (Microsoft Excel)
     *
     * @param mixed $title Title or filepath
     *         Title object of document (must be of type NS_FILE)
     *         or a filepath in the filesystem
     * @return array e.g. [ text => extracted text of document, xml => full XML-response of Tika ]
     * @throws Exception
     */
    public function extractDocument($title) {
        if ($title instanceof Title) {
            $file = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo()->newFile($title);
            $filepath = $file->getLocalRefPath();
        } else {
            $filepath = $title;
        }

        // get file and extension
        $ext = pathinfo($filepath, PATHINFO_EXTENSION);

        // choose content type
        if ($ext == 'pdf') {
            $contentType = 'application/pdf';
        } else if ($ext == 'doc' || $ext == 'docx') {
            $contentType = 'application/msword';
        } else if ($ext == 'ppt' || $ext == 'pptx') {
            $contentType = 'application/vnd.ms-powerpoint';
        } else if ($ext == 'xls' || $ext == 'xlsx') {
            $contentType = 'application/vnd.ms-excel';
        } else {
            // general binary data as fallback (don't know if Tika accepts it)
            $contentType = 'application/octet-stream';
        }

        // do not index unknown formats
        if ($contentType == 'application/octet-stream') {
            return [];
        }

        // send document to Tika and extract text
        try {
            $text = $this->requestFileExtraction(file_get_contents($filepath), $contentType);

            if ($text == '') {
                throw new Exception(sprintf("\nWARN Kein extrahierter Text gefunden: %s\n", $title->getPrefixedText()));
            }

            return ['text' => $text];
        } catch (Exception $e) {
            throw new Exception(sprintf("\nERROR Keine Extraktion möglich: %s (HTTP code: %s)\n",
                $title->getPrefixedText(), $e->getCode()));
        }
    }

    private function getParams(string $searchText,
                               array $propertyFacetConstraints, /* @var PropertyFacet[] */
                               array $categoryFacets, /* @var string [] */
                               array $namespaceFacets, /* @var number[] */
                               array $extraProperties/* @var PropertyFacet[] */
    )
    {


        $defaultProperties = [
            'smwh__MDAT_datevalue_l',
            'smwh_categories',
            'smwh_directcategories',
            'smwh_attributes',
            'smwh_properties',
            'smwh_title',
            'smwh_namespace_id',
            'id',
            'score',
            'smwh_displaytitle'
        ];

        $extraPropertiesAsStrings = array_map(fn(Property $e) => Helper::generateSOLRProperty($e->title, $e->type), $extraProperties);

        $params = [];
        $params['defType'] = 'edismax';
        $params['boost'] = 'max(smwh_boost_dummy)';
        $params['facet'] = 'true';
        $params['facet.field'] = ['smwh_categories', 'smwh_attributes', 'smwh_properties', 'smwh_namespace_id'];


        $params['facet.mincount'] = '1';
        $params['json.nl'] = 'map';
        $params['fl'] = implode(",", array_merge($defaultProperties, $extraPropertiesAsStrings));
        $params['hl'] = 'true';
        $params['hl.method'] = 'unified';
        $params['hl.fl'] = 'smwh_search_field';
        $params['hl.simple-pre'] = '<b>';
        $params['hl.simple-post'] = '</b>';
        $params['hl.fragsize'] = '250';
        $searchText = trim($searchText);
        $params['searchText'] = $searchText === '' ? '(*)' : $searchText;

        $params['wt'] = 'json';

        $fq = array_merge(
            self::encodePropertyFacets($propertyFacetConstraints),
            self::encodeCategoryFacets($categoryFacets),
            self::encodeNamespaceFacets($namespaceFacets),
            self::encodeNamespaceConstraints()
        );
        $params['fq'] = $fq;

        $params['q.alt'] = $searchText === '' ? 'smwh_search_field:(*)' : self::encodeSearchQuery(preg_split("/\s+/", $searchText));

        return $params;
    }

    private static function encodeNamespaceConstraints(): array
    {
        global $fs2gNamespaceConstraint;
        if (!isset($fs2gNamespaceConstraint) || count($fs2gNamespaceConstraint) === 0) {
            return [];
        }

        $userGroups = WikiTools::getUserGroups();

        $constraints = [];
        foreach ($fs2gNamespaceConstraint as $group => $namespaces) {
            if (in_array($group, $userGroups)) {
                foreach ($namespaces as $namespace) {
                    $constraints[] = "smwh_namespace_id:$namespace";
                }
            }
        }
        $constraints = array_unique($constraints);
        if (count($constraints) > 0) {
            return [implode(' OR ', $constraints)];
        }

        return [];
    }

    private function encodeSortsAndLimits(array $sorts /* @var Sort[] */, $limit, $offset): array
    {
        $params = [];
        $params['rows'] = $limit;
        $params['start'] = $offset;
        $params['sort'] = self::serializeSortsCommaSeparated($sorts);
        return $params;
    }

    private static function encodePropertyFacets(array $propertyFacets): array
    {
        if (count($propertyFacets) === 0) {
            return [];
        }
        $result = [];
        foreach ($propertyFacets as $f) {
            $propertyConstraints = [];
            $valueConstraints = [];

            foreach ($f->getValues() as $v) {
                /* @var $f PropertyFacet */
                if ($f->property->type === Datatype::WIKIPAGE) {
                    $pAsValue = 'smwh_properties:' . Helper::generateSOLRProperty($f->property->title, Datatype::WIKIPAGE);
                    if (!in_array($pAsValue, $propertyConstraints)) {
                        $propertyConstraints[] = $pAsValue;
                    }
                    if (!is_null($v->mwTitle)) {
                        $p = Helper::generateSOLRPropertyForSearch($f->property->title, Datatype::WIKIPAGE);
                        $value = Helper::quoteValue($v->mwTitle->title . '|' . $v->mwTitle->displayTitle, Datatype::WIKIPAGE);
                        $valueConstraints[] = $p . ':' . $value;
                    }
                } else {
                    $pAsValue = 'smwh_attributes:' . Helper::generateSOLRProperty($f->property->title, $f->property->type);
                    if (!in_array($pAsValue, $propertyConstraints)) {
                        $propertyConstraints[] = $pAsValue;
                    }
                    $p = Helper::generateSOLRPropertyForSearch($f->property->title, $f->property->type);

                    if (!is_null($v->range)) {
                        $value = "[" . self::encodeRange($v->range, $f->property->type) . "]";
                        $valueConstraints[] = "$p:$value";
                    } else if (!is_null($v->value) && ($f->property->type === Datatype::STRING || $f->property->type === Datatype::NUMBER || $f->property->type === Datatype::BOOLEAN
                            || $f->property->type === Datatype::DATETIME)) {
                        $value = Helper::quoteValue($v->value, $f->property->type);
                        $valueConstraints[] = "$p:$value";
                    }
                }
            }

            if (count($valueConstraints) > 0) {
                global $fs2gFacetsWithOR;
                if (in_array($f->property->getTitle(), $fs2gFacetsWithOR)) {
                    $result = array_merge($result, $propertyConstraints, ["(" . implode(' OR ', $valueConstraints) . ")"]);
                } else {
                    $result = array_merge($result, $propertyConstraints, ["(" . implode(' AND ', $valueConstraints) . ")"]);
                }
            } else {
                $result = array_merge($result, $propertyConstraints, $valueConstraints);
            }

        }
        return $result;
    }


    private static function encodeRange(Range $range, $type): string
    {
        if ($type === Datatype::DATETIME) {
            return Helper::convertDateTimeToLong($range->from) . " TO " . Helper::convertDateTimeToLong($range->to);
        }
        return $range->from . " TO " . $range->to;
    }

    private static function encodeCategoryFacets(array $categories/* @var string[] */): array
    {
        $facetValues = [];
        foreach ($categories as $category) {
            $pAsValue = 'smwh_categories:' . Helper::quoteValue($category, Datatype::WIKIPAGE);
            if (!in_array($pAsValue, $facetValues)) {
                $facetValues[] = $pAsValue;
            }
        }
        return $facetValues;
    }

    private static function encodeNamespaceFacets(array $namespaces/* @var int[] */): array
    {
        $facetValues = [];
        foreach ($namespaces as $namespace) {
            $pAsValue = 'smwh_namespace_id:' . $namespace;
            if (!in_array($pAsValue, $facetValues)) {
                $facetValues[] = $pAsValue;
            }
        }
        return [join(' OR ', $facetValues)];
    }

    private static function serializeSortsCommaSeparated(array $sorts): string
    {
        $arr = array_map(function (Sort $s) {
            $order = $s->order === Order::DESC ? 'desc' : 'asc';
            if ($s->property->type === Datatype::INTERNAL) {
                if ($s->property->title === 'score') {
                    return "score $order";
                } else if ($s->property->title === 'displaytitle') {
                    return "smwh_displaytitle $order";
                }
                throw new Exception("unknown special property: {$s->property->title}");
            } else {
                $property = Helper::generateSOLRPropertyForSearch($s->property->title, $s->property->type);
                return "$property $order";
            }
        }, $sorts);

        return implode(", ", $arr);
    }

    private static function encodeSearchQuery(array $terms): string
    {
        $searchTerms = implode(' AND ', $terms);
        $searchTermsWithPlus = implode(' AND ', array_map(fn($e) => "+$e", $terms));

        return "smwh_search_field:(${searchTermsWithPlus}* ) OR "
            . "smwh_search_field:(${searchTerms}) OR "
            . "smwh_title:(${searchTerms}) OR "
            . "smwh_displaytitle:(${searchTerms})";
    }

    private function requestSOLR(array $queryParams)
    {
        try {
            $headerFields = [];
            $headerFields[] = "Content-Type: application/x-www-form-urlencoded; charset=UTF-8";
            $headerFields[] = "Expect:"; // disables 100 CONTINUE
            $ch = curl_init();
            $queryString = Util::buildQueryParams($queryParams);
            $url = Helper::getSOLRBaseUrl() . "/select";

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $queryString);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20); //timeout in seconds

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                throw new Exception("Error on request: $error_msg");
            }
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            list($header, $body) = Util::splitResponse($response);
            if ($httpcode >= 200 && $httpcode <= 299) {

                return json_decode($body);

            }
            throw new Exception("Error on select-request. HTTP status: $httpcode. Message: $body");

        } finally {
            curl_close($ch);
        }
    }

    public function requestFileExtraction(string $fileContent, string $contentType): string
    {
        try {
            $headerFields = [];
            $headerFields[] = "Content-Type: $contentType";
            $headerFields[] = "Expect:"; // disables 100 CONTINUE
            $ch = curl_init();

            $url = Helper::getSOLRBaseUrl() . "/update/extract?extractOnly=true&wt=json";

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20); //timeout in seconds

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                throw new Exception("Error on request: $error_msg");
            }
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            list($header, $body) = Util::splitResponse($response);
            if ($httpcode >= 200 && $httpcode <= 299) {

                $result = json_decode($body);
                $xml = $result->{''};
                $text = strip_tags(str_replace('<', ' <', $xml));
                return preg_replace('/\s\s*/', ' ', $text);

            }
            throw new Exception("Error on select-request. HTTP status: $httpcode. Message: $body");

        } finally {
            curl_close($ch);
        }
    }


    /**
     * Fills empty category facet counts if category facet is selected but there are no results
     */
    private function fillEmptyCategoryFacetCounts(DocumentsResponse $docResponse, DocumentQuery $q): void
    {
        $categoriesInFacetCounts = array_map(fn($e) => $e->category, $docResponse->categoryFacetCounts);
        foreach ($q->categoryFacets as $c) {
            if (!in_array($c, $categoriesInFacetCounts)) {
                $docResponse->categoryFacetCounts[] = new CategoryFacetCount($c, WikiTools::getDisplayTitleForCategory($c), 0);
            }
        }
    }

}
