<?php

namespace DIQA\FacetedSearch2\ElasticSearch;


use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;

abstract class AbstractElasticSearchClient
{
    protected Client $client;
    protected array $config;

    /**
     * @throws AuthenticationException
     */
    public function __construct()
    {
        global $fs2gBackendConfig;
        $config = [

            'host' => $fs2gBackendConfig['host'] ?? 'localhost',
            'port' => $fs2gBackendConfig['port'] ?? 9200,
            'user' => $fs2gBackendConfig['user'] ?? 'elastic',
            'pass' => $fs2gBackendConfig['pass'] ?? '',
            'ssl' => $fs2gBackendConfig['ssl'] ?? true,
            'verify-ssl' => $fs2gBackendConfig['verify-ssl'] ?? false,

        ];

        $protocol = $config['ssl'] ? 'https' : 'http';
        $this->client = ClientBuilder::create()
            ->setSSLVerification($config['verify-ssl'])
            ->setHosts(["$protocol://" . $config['host'] . ':' . $config['port']])
            ->setBasicAuthentication($config['user'], $config['pass'])
            ->build();
        $this->config = $config;
    }

    protected function getParamForIndex(): array
    {
        global $fs2gBackendConfig;

        return [
            'index' => $fs2gBackendConfig['indexName'] ?? 'mw',
        ];
    }
}

