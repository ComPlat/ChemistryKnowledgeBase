<?php

namespace DIQA\ChemExtension\PubChem;

use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;

class PubChemService {
    private $moleculeRGroupServiceUrl;
    private $logger;

    public function __construct()
    {
        $this->logger = new LoggerUtils('PubChemService', 'ChemExtension');
        $this->moleculeRGroupServiceUrl = 'https://pubchem.ncbi.nlm.nih.gov';
    }

    public function getRecord(string $inchiKey) {
        return $this->getJsonData("/rest/pug/compound/inchikey/$inchiKey/record/JSON");
    }

    public function getSynonyms(string $inchiKey) {
        return $this->getJsonData("/rest/pug/compound/inchikey/$inchiKey/synonyms/JSON");
    }

    public function getCategories(string $cid) {
        if (is_null($cid)) {
            return new \stdClass();
        }
        return $this->getJsonData("/rest/pug_view/categories/compound/$cid/JSON");
    }

    /**
     * @throws Exception
     */
    private function getJsonData(string $url)
    {
        try {
            $headerFields = [];
            $headerFields[] = "Expect:"; // disables 100 CONTINUE
            $ch = curl_init();
            $url = $this->moleculeRGroupServiceUrl . $url;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                throw new Exception("Error on request: " . $error_msg);
            }
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            list($header, $body) = self::splitResponse($response);
            $parsedBody = json_decode($body);
            if ($httpcode >= 200 && $httpcode <= 299) {
                $this->logger->log("Result: " . print_r($parsedBody, true));
                if (isset($parsedBody->Fault)) {
                    throw new Exception($parsedBody->Fault->Message ?? 'Unknown Error');
                }
                return $parsedBody;
            }
            if (isset($parsedBody->Fault)) {
                throw new Exception($parsedBody->Fault->Message ?? 'Unknown Error');
            }
            throw new Exception("Error on pubchem request. HTTP status " . $httpcode . ". Message: " . $body);

        } finally {
            curl_close($ch);
        }
    }

    protected static function splitResponse($res): array
    {
        $bodyBegin = strpos($res, "\r\n\r\n");
        list($header, $res) = $bodyBegin !== false ? array(substr($res, 0, $bodyBegin), substr($res, $bodyBegin + 4)) : array($res, "");
        return array($header, str_replace("%0A%0D%0A%0D", "\r\n\r\n", $res));
    }
}
