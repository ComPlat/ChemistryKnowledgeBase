<?php

namespace DIQA\ChemExtension\TIB;

use DIQA\ChemExtension\Utils\CurlUtil;
use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;

class TibClient {
    
    private $tibServiceUrl;
    private $logger;

    public function __construct()
    {
        global $wgTibServiceUrl;
        $this->tibServiceUrl = $wgTibServiceUrl ?? 'https://service.tib.eu/ts4tib/api';
        $this->logger = new LoggerUtils('TibClient', 'ChemExtension');
    }

    /**
     * @throws Exception
     */
    function suggest(string $searchText, int $maxResults = 100)
    {
        try {
            $headerFields = [];
            $headerFields[] = "Expect:"; // disables 100 CONTINUE
            $ch = curl_init();
            $url = $this->tibServiceUrl . "/suggest?q=%s&rows=%s";
            $url = sprintf($url, urlencode($searchText), $maxResults);

            $this->logger->log("Request URL: $url");
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); //timeout in seconds

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                throw new Exception("Error on request: $error_msg");
            }
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            list($header, $body) = CurlUtil::splitResponse($response);
            if ($httpcode >= 200 && $httpcode <= 299) {
                $this->logger->log("Result: " . print_r($body, true));
                $result = json_decode($body);
                return $this->parseResults($result);
            }
            throw new Exception("Error. HTTP status: $httpcode. Message: $body");

        } finally {
            curl_close($ch);
        }
    }

    private function parseResults($curlResult) {
        if (!isset($curlResult->response->docs)) {
            return [];
        }
        return array_map(fn($e) => $e->autosuggest, $curlResult->response->docs);
    }
}