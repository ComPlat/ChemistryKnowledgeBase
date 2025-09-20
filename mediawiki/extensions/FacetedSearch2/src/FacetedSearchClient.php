<?php

namespace DIQA\FacetedSearch2;

use DIQA\FacetedSearch2\Model\Request\DocumentQuery;
use DIQA\FacetedSearch2\Model\Request\FacetQuery;
use DIQA\FacetedSearch2\Model\Request\StatsQuery;
use DIQA\FacetedSearch2\Model\Response\Document;
use DIQA\FacetedSearch2\Model\Response\DocumentsResponse;
use DIQA\FacetedSearch2\Model\Response\FacetResponse;
use DIQA\FacetedSearch2\Model\Response\StatsResponse;

interface FacetedSearchClient
{
    /**
     * Requests a set of documents.
     *
     * @param DocumentQuery $q
     * @return DocumentsResponse
     */
    public function requestDocuments(DocumentQuery $q): DocumentsResponse;

    /**
     * Requests a set of facets, ie. a list of properties with values and their frequency.
     *
     * @param FacetQuery $q
     * @return FacetResponse
     */
    public function requestFacets(FacetQuery $q): FacetResponse;

    /**
     * Requests statistical data about a property, ie. min/max values. Generates and returns
     * pre-calculated clusters from this data.
     * @param StatsQuery $q
     * @return StatsResponse
     */
    public function requestStats(StatsQuery $q): StatsResponse;

    /**
     * Extracts fulltext from a file.
     *
     * @param string $fileContent The raw content of the file
     * @param string $contentType The content media type (eg. application/pdf)
     * @return string The extracted full-text
     */
    public function requestFileExtraction(string $fileContent, string $contentType): string;

    /**
     * Returns a single document by ID with ALL its properties.
     *
     * @param string $id
     * @return Document
     */
    public function requestDocument(string $id): Document;
}