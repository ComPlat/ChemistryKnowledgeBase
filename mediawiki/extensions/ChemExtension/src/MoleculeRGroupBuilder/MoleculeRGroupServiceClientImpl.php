<?php

namespace DIQA\ChemExtension\MoleculeRGroupBuilder;

use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;

class MoleculeRGroupServiceClientImpl implements MoleculeRGroupServiceClient
{

    private $moleculeRGroupServiceUrl;
    private $logger;

    public function __construct()
    {
        global $wgMoleculeRGroupServiceUrl;
        $moleculeRGroupServiceUrl = $wgMoleculeRGroupServiceUrl ?? null;
        if (is_null($moleculeRGroupServiceUrl)) {
            throw new Exception('Molecule R-Groups service is not properly configured. Set $wgMoleculeRGroupServiceUrl.');
        }
        $this->logger = new LoggerUtils('MoleculeRGroupServiceClientImpl', 'ChemExtension');
        $this->moleculeRGroupServiceUrl = $moleculeRGroupServiceUrl;
    }

    /**
     * @throws Exception
     */
    function buildMolecules(string $molfile, array $rGroups)
    {
        try {
            $headerFields = [];
            $headerFields[] = "Content-Type: application/json";
            $headerFields[] = "Expect:"; // disables 100 CONTINUE
            $ch = curl_init();
            $url = $this->moleculeRGroupServiceUrl . "/api/v1/rgroup/";
            $payload = new \stdClass();
            $payload->mdl = $molfile;
            $payload->rgroups = self::makeRGroupsUppercase($rGroups);
            $this->logger->log("Request payload: " . json_encode($payload));
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                throw new Exception("Error on request: $error_msg");
            }
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            list($header, $body) = self::splitResponse($response);
            if ($httpcode >= 200 && $httpcode <= 299) {
                $this->logger->log("Result: " . print_r($body, true));
                return json_decode($body);
            }
            throw new Exception("Error on upload. HTTP status: $httpcode. Message: $body");

        } finally {
            curl_close($ch);
        }
    }

    private static function makeRGroupsUppercase($rGroups): array
    {
        $results = [];
        foreach ($rGroups as $group) {
            $result = [];
            foreach ($group as $r => $value) {
                $result[strtoupper($r)] = $value;
            }
            $results[] = $result;
        }
        return $results;
    }

    protected static function splitResponse($res): array
    {
        $bodyBegin = strpos($res, "\r\n\r\n");
        list($header, $res) = $bodyBegin !== false ? array(substr($res, 0, $bodyBegin), substr($res, $bodyBegin + 4)) : array($res, "");
        return array($header, str_replace("%0A%0D%0A%0D", "\r\n\r\n", $res));
    }
}
