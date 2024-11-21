<?php

namespace DIQA\ChemExtension\Indigo;

use DIQA\ChemExtension\Utils\CurlUtil;
use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;

class IndigoClient {

    private $indigoServiceUrl;
    private $logger;

    public function __construct()
    {
        global $wgIndigoServiceUrl;
        $indigoServiceUrl = $wgIndigoServiceUrl ?? null;
        if (is_null($indigoServiceUrl)) {
            throw new Exception('Indigo service is not properly configured. Set $wgIndigoServiceUrl.');
        }
        $this->logger = new LoggerUtils('IndigoClient', 'ChemExtension');
        $this->indigoServiceUrl = $indigoServiceUrl;
    }

    public function convertToMolfile(string $smiles) {
        $payload = new \stdClass();
        $payload->struct = $smiles;
        $payload->output_format = "chemical/x-mdl-molfile";
        $result = $this->request('/convert', $payload);
        return $result->struct;
    }

    /**
     * @throws Exception
     */
    private function request(string $path, $payload)
    {
        try {
            $headerFields = [];
            $headerFields[] = "Content-Type: application/json";
            $headerFields[] = "Expect:"; // disables 100 CONTINUE
            $ch = curl_init();
            $url = $this->indigoServiceUrl . $path;
            $this->logger->log("Request payload: " . json_encode($payload));
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
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

            list($header, $body) = CurlUtil::splitResponse($response);
            if ($httpcode >= 200 && $httpcode <= 299) {
                $this->logger->log("Result: " . print_r($body, true));
                return json_decode($body);
            }
            throw new Exception("Error on upload. HTTP status: $httpcode. Message: $body");

        } finally {
            curl_close($ch);
        }
    }

}
