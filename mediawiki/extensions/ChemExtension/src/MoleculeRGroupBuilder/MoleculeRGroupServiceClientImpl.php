<?php

namespace DIQA\ChemExtension\MoleculeRGroupBuilder;

use DIQA\ChemExtension\CheckServiceRequest;
use DIQA\ChemExtension\Pages\ChemForm;
use DIQA\ChemExtension\Utils\ArrayTools;
use DIQA\ChemExtension\Utils\CurlUtil;
use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;

class MoleculeRGroupServiceClientImpl implements MoleculeRGroupServiceClient, CheckServiceRequest
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

    public function check(): \CurlHandle|false
    {
        $benzolWithRGroupsMolfile = <<<MOL

  -INDIGO-01122317072D

  0  0  0  0  0  0  0  0  0  0  0 V3000
M  V30 BEGIN CTAB
M  V30 COUNTS 7 7 0 0 0
M  V30 BEGIN ATOM
M  V30 1 C 2.80985 -5.95007 0.0 0
M  V30 2 C 4.54015 -5.94959 0.0 0
M  V30 3 C 3.67664 -5.44997 0.0 0
M  V30 4 C 4.54015 -6.95053 0.0 0
M  V30 5 C 2.80985 -6.95502 0.0 0
M  V30 6 C 3.67882 -7.45003 0.0 0
M  V30 7 R# 5.375 -7.575 0.0 0 RGROUPS=(1 4)
M  V30 END ATOM
M  V30 BEGIN BOND
M  V30 1 2 3 1
M  V30 2 2 4 2
M  V30 3 1 1 5
M  V30 4 1 2 3
M  V30 5 2 5 6
M  V30 6 1 6 4
M  V30 7 1 7 4
M  V30 END BOND
M  V30 END CTAB
M  END
MOL;
        $headerFields = [];
        $headerFields[] = "Content-Type: application/json";
        $headerFields[] = "Expect:"; // disables 100 CONTINUE
        $ch = curl_init();
        $url = $this->moleculeRGroupServiceUrl . "/api/v1/rgroup/";
        $payload = new \stdClass();
        $payload->mdl = $benzolWithRGroupsMolfile;
        $payload->rgroups = self::makeRGroupsUppercase([['r4' => 'ACE']]);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20); //timeout in seconds
        return $ch;
    }
}
