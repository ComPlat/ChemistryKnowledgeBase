<?php

namespace DIQA\ChemExtension\MoleculeRGroupBuilder;

class MoleculeRGroupServiceClientImpl implements MoleculeRGroupServiceClient {

    private $moleculeRestServiceUrl;

    public function __construct() {
        global $wgMoleculeRestServiceUrl;
        $moleculeRestServiceUrl = $wgMoleculeRestServiceUrl ?? null;
        if (is_null($moleculeRestServiceUrl)) {
            throw new Exception('Molecule rests service is not properly configured. Set $wgMoleculeRestServiceUrl.');
        }

        $this->moleculeRestServiceUrl = $moleculeRestServiceUrl;
    }
    function buildMolecules(string $molfile, array $moleculeRests)
    {
        try {
            $headerFields = [];
            $headerFields[] = "Content-Type: application/json";
            $headerFields[] = "Expect:"; // disables 100 CONTINUE
            $ch = curl_init();
            $url = $this->moleculeRestServiceUrl . "/api/v1/..."; // TODO: change path
            $payload = new \stdClass();
            $payload->molfile = $molfile;
            $payload->rests = $moleculeRests;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,  json_encode($payload));
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerFields );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                throw new Exception("Error on request: " . $error_msg);
            }
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            list($header, $body) = self::splitResponse($response);
            if ($httpcode >= 200 && $httpcode <= 299) {
                return json_decode($body);
            }
            throw new Exception("Error on upload. HTTP status " . $httpcode . ". Message: " . $body);

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
