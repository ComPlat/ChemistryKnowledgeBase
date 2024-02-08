<?php

namespace DIQA\ChemExtension\PubChem;

use DIQA\ChemExtension\Utils\CurlUtil;
use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;

class PubChemClient {
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
            throw new Exception("Error on PubChem request. HTTP status: $httpcode. Message: $body for $url");

        } finally {
            curl_close($ch);
        }
    }

}
