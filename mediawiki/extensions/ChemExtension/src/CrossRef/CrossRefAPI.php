<?php

namespace DIQA\ChemExtension\CrossRef;

use DIQA\ChemExtension\Utils\CurlUtil;
use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;
class CrossRefAPI {

    private $logger;
    private $crossRefApiBaseUrl;
    public function __construct()
    {
        $this->logger = new LoggerUtils('CrossRefAPI', 'ChemExtension');
        $this->crossRefApiBaseUrl = 'https://api.crossref.org';
    }

    public function find(string $query, int $daysAgo = 30, $additionalFilters = []) {

        $filters = [
            'from-created-date' => date('Y-m-d', strtotime("-$daysAgo days")),
            'has-abstract' => true
        ];
        $filtersAsStrings = array_map(fn($k) => "$k:$filters[$k]", array_keys($filters));

        return $this->getJsonData('/works', array_merge( [
            'query' => $query,
            'select' => 'title,abstract,DOI,published',
            'filter' => implode(',', $filtersAsStrings),
            'sort' => 'published',
            'order' => 'desc'
        ], $additionalFilters));
    }


    /**
     * @throws Exception
     */
    private function getJsonData(string $url, array $queryParams = [])
    {
        try {
            $headerFields = [];
            $headerFields[] = "Expect:"; // disables 100 CONTINUE
            $ch = curl_init();
            $url = $this->crossRefApiBaseUrl . $url . '?' . CurlUtil::buildQueryParams($queryParams);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                throw new Exception("Error on request: $error_msg for $url");
            }
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            list($header, $body) = CurlUtil::splitResponse($response);
            $parsedBody = json_decode($body);
            if ($httpcode >= 200 && $httpcode <= 299 && !isset($parsedBody->Fault)) {
                return $parsedBody;
            }
            if (isset($parsedBody->Fault)) {
                $errMsg = $parsedBody->Fault->Message ?? 'Unknown Error';
                $errMsg .= " for $url";
                throw new Exception($errMsg);
            }
            throw new Exception("Error on CrossRef request. HTTP status: $httpcode. Message: $body for $url");

        } finally {
            curl_close($ch);
        }
    }

}
