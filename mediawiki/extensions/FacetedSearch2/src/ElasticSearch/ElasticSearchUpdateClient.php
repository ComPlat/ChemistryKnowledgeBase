<?php

namespace DIQA\FacetedSearch2\ElasticSearch;

use DIQA\FacetedSearch2\Exceptions\BackendException;
use DIQA\FacetedSearch2\FacetedSearchUpdateClient;
use DIQA\FacetedSearch2\Model\Common\Datatype;
use DIQA\FacetedSearch2\Model\Update\Document;
use DIQA\FacetedSearch2\Model\Update\PropertyValues;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;

class ElasticSearchUpdateClient extends AbstractElasticSearchClient implements FacetedSearchUpdateClient
{
    const int MAX_BULK_SIZE = 1000;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws BackendException
     */
    public function updateDocuments(...$docs): void
    {
        global $fs2gBackendConfig;

        $params = ['body' => [] ];
        $i = 0;
        try {
            foreach ($docs as $doc) {
                $params['body'][] = [
                    'index' => [
                        '_index' => $fs2gBackendConfig['indexName'] ?? 'mw',
                        '_id' => $doc->getId(),
                    ],
                ];
                $params['body'][] = $this->getDocumentBody($doc);

                if ($i % self::MAX_BULK_SIZE == 0) {

                    $responses = $this->client->bulk($params);
                    $params = ['body' => []];
                    unset($responses);
                }
                $i++;
            }

            if (!empty($params['body'])) {
                $this->client->bulk($params);
            }
        } catch (
        ClientResponseException
        |ServerResponseException $e) {
            throw BackendException::create($e);
        }
    }

    /**
     * @throws BackendException
     */
    public function deleteDocument(string $id): void
    {
        $params = $this->getParamForIndex();
        $params['id'] = $id;

        try {
            $this->client->delete($params);
        } catch (ClientResponseException
        |MissingParameterException
        |ServerResponseException $e) {
            throw BackendException::create($e);
        }
    }

    /**
     * @throws BackendException
     */
    public function clearAllDocuments(): void
    {
        $params = $this->getParamForIndex();
        $params['body'] = ['query' => ['match_all' => new \stdClass()]];
        try {
            $this->client->deleteByQuery($params);
        } catch (ClientResponseException
        |MissingParameterException
        |ServerResponseException $e) {
            throw BackendException::create($e);
        }
    }

    /**
     * @throws BackendException
     */
    public function deleteIndex(): void
    {
        $params = $this->getParamForIndex();
        try {
            $this->client->indices()->delete($params);
        } catch (ClientResponseException
        |MissingParameterException
        |ServerResponseException $e) {
            throw BackendException::create($e);
        }

    }

    /**
     * @throws BackendException
     */
    public function initIndex(): bool
    {
        $params = $this->getParamForIndex();

        try {
            $response = $this->client->indices()->exists($params);
            if ($response->asBool()) {
                return true;
            }

            $params['body'] = $this->getSchemaMappings();

            $this->client->indices()->create($params);
            return true;
        } catch (ClientResponseException
        |MissingParameterException
        |ServerResponseException $e) {
            throw BackendException::create($e);
        }

    }

    /**
     * @return array[]
     */
    private function getSchemaMappings(): array
    {
        $schemaProperties = [];
        $schemaProperties['__categories'] = ['type' => 'keyword'];
        $schemaProperties['__directCategories'] = ['type' => 'keyword'];
        $schemaProperties['__templates'] = ['type' => 'keyword'];
        $schemaProperties['__properties'] = ['type' => 'keyword'];
        $schemaProperties['__fulltext'] = [
            'type' => 'text',
            'analyzer' => 'substring_analyzer',
            'search_analyzer' => 'substring_search_analyzer'];
        $schemaProperties['__title'] = [
            'type' => 'text',
            'analyzer' => 'substring_analyzer',
            'search_analyzer' => 'substring_search_analyzer'
        ];
        $schemaProperties['__namespace'] = ['type' => 'long'];
        $schemaProperties['__display'] = ['type' => 'wildcard'];
        return [
            "settings" => [
                "analysis" => [
                    "analyzer" => [
                        "substring_analyzer" => [
                            "type" => "custom",
                            "tokenizer" => "standard",
                            "filter" => ["lowercase", "substring_ngram"]
                        ],
                        "substring_search_analyzer" => [
                            "type" => "custom",
                            "tokenizer" => "standard",
                            "filter" => ["lowercase"]
                        ]
                    ],
                    "filter" => [
                        "substring_ngram" => [
                            "type" => "ngram",
                            "min_gram" => 2,
                            "max_gram" => 20
                        ]
                    ]
                ],
                "index" => [
                    "max_ngram_diff" => 18
                ]
            ],
            'mappings' => [
                'properties' => $schemaProperties,
                "dynamic_templates" => [
                    [

                        "fs2-number-template" => [
                            "match" => "number_*",
                            "mapping" => [
                                "type" => "double"
                            ]
                        ],
                    ],
                    [

                        "fs2-text-template" => [
                            "match" => "text_*",
                            "mapping" => [
                                "type" => "wildcard"
                            ]
                        ],
                    ],
                    [

                        "fs2-datetime-template" => [
                            "match" => "datetime_*",
                            "mapping" => [
                                "type" => "date"
                            ]
                        ],
                    ],
                    [

                        "fs2-boolean-template" => [
                            "match" => "boolean_*",
                            "mapping" => [
                                "type" => "boolean"
                            ]
                        ],
                    ],
                    [

                        "fs2-wikipage-template" => [
                            "match" => "wikipage_*",
                            "mapping" => [
                                "type" => "nested",
                                "properties" => [
                                    "title" => ["type" => "wildcard"],
                                    "display" => ["type" => "wildcard"]
                                ]
                            ]
                        ],
                    ]
                ]
            ],
        ];
    }

    /**
     * Check if the index exists
     * @return bool
     * @throws BackendException
     */
    public function existsIndex(): bool
    {
        try {
            $params = $this->getParamForIndex();
            $response = $this->client->indices()->exists($params);
            return $response->asBool();
        } catch (
        ClientResponseException
        |MissingParameterException
        |ServerResponseException $e) {
            throw BackendException::create($e);
        }
    }

    /**
     * @throws BackendException
     */
    public function refreshIndex(): void
    {
        try {
            $params = $this->getParamForIndex();
            $this->client->indices()->refresh($params);
        } catch (
        ClientResponseException
        |ServerResponseException $e) {
            throw BackendException::create($e);
        }
    }

    /**
     * @param Document $doc
     * @return array
     */
    private function getDocumentBody(Document $doc): array
    {
        $body = [];
        $body['__categories'] = $doc->getCategories();
        $body['__directCategories'] = $doc->getDirectCategories();
        $body['__templates'] = $doc->getTemplates();
        $properties = array_map(fn(PropertyValues $pv) =>
            Helper::toInternalName($pv->getProperty()), $doc->getPropertyValues());
        $body['__properties'] = array_values(array_unique($properties));
        $body['__fulltext'] = $doc->getFulltext();
        $body['__title'] = $doc->getTitle();
        $body['__namespace'] = $doc->getNamespace();
        $body['__display'] = $doc->getDisplayTitle();
        $propertyValues = $doc->getPropertyValues();
        foreach ($propertyValues as $propertyValue) {
            $name = Helper::toInternalName($propertyValue->getProperty());
            $body[$name] = self::mapValuesForUpdateToESModel($propertyValue);
        }
        return $body;
    }

    private static function mapValuesForUpdateToESModel(PropertyValues $values): array
    {
        $result = [];
        switch ($values->getProperty()->getType()) {

            case Datatype::WIKIPAGE:
                foreach ($values->getMwTitles() as $value) {
                    $value = [
                        "title" => $value->getTitle(),
                        "display" => $value->getDisplayTitle()
                    ];
                    $result[] = $value;
                }
                break;
            default:
                foreach ($values->getValues() as $value) {
                    $result[] = $value;
                }
                break;
        }
        return $result;
    }

}