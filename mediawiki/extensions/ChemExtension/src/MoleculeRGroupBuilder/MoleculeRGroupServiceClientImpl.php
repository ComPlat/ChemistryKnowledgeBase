<?php

namespace DIQA\ChemExtension\MoleculeRGroupBuilder;

use DIQA\ChemExtension\Pages\ChemForm;
use DIQA\ChemExtension\Utils\ArrayTools;
use DIQA\ChemExtension\Utils\CurlUtil;
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
                $result = json_decode($body);
                $concreteMolecules = [];
                foreach($result->rgroup as $m) {
                    $concreteMolecules[] = [
                        'chemForm' => ChemForm::fromMolOrRxn($m->mdl, $m->smiles, $m->inchi, $m->inchikey),
                        'rGroups' => self::makeRGroupsLowercase($m)
                    ];
                }
                return $concreteMolecules;
            }
            throw new Exception("Error on upload. HTTP status: $httpcode. Message: $body");

        } finally {
            curl_close($ch);
        }
    }

    private static function makeRGroupsLowercase($molecule) {
        $result = [];
        $arr = ArrayTools::propertiesToArray($molecule);
        foreach($arr as $key => $value) {
            if (preg_match("/^r\d+/i", $key)) {
                $result[strtolower($key)] = $value;
            }
        }
        return $result;
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

    function getMetadata(string $molfile): array
    {
        try {
            $headerFields = [];
            $headerFields[] = "Content-Type: application/json";
            $headerFields[] = "Expect:"; // disables 100 CONTINUE
            $ch = curl_init();
            $url = $this->moleculeRGroupServiceUrl . "/api/v1/molecules/";
            $payload = new \stdClass();
            $payload->mdl = $molfile;
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
                $result = json_decode($body);
                $metadata = [];
                $metadata['molecularMass'] = $result->molecular_weight ?? '';
                $metadata['molecularFormula'] = $result->formula ?? '';
                return $metadata;
            }
            throw new Exception("Error on upload. HTTP status: $httpcode. Message: $body");

        } finally {
            curl_close($ch);
        }
    }

    function getAvailableRGroups(): array
    {
        try {
            $headerFields = [];
            $headerFields[] = "Content-Type: application/json";
            $headerFields[] = "Expect:"; // disables 100 CONTINUE
            $ch = curl_init();
            $url = $this->moleculeRGroupServiceUrl . "/api/v1/superatoms/keys";
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
                $result = json_decode($body);
                return $result->keys ?? [];
            }
            throw new Exception("Error on upload. HTTP status: $httpcode. Message: $body");

        } finally {
            curl_close($ch);
        }
    }
}
