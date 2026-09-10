<?php
namespace DIQA\ChemExtension\MoleculeRenderer;

use DIQA\ChemExtension\CheckServiceRequest;
use DIQA\ChemExtension\Utils\CurlUtil;
use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;

class MoleculeRendererClientImpl implements CheckServiceRequest {
    private $moleculeRendererServiceUrl;
    private $logger;

    public function __construct()
    {
        global $moleculeRendererServiceUrl;
        $moleculeRendererServiceUrl = $moleculeRendererServiceUrl ?? null;
        if (is_null($moleculeRendererServiceUrl)) {
            throw new Exception('Molecule Render service is not properly configured. Set $moleculeRendererServiceUrl.');
        }
        $this->logger = new LoggerUtils('MoleculeRendererClientImpl', 'ChemExtension');
        $this->moleculeRendererServiceUrl = $moleculeRendererServiceUrl;
    }

    /**
     * @throws Exception
     */
    function render(string $molfile)
    {
        try {
            $headerFields = [];
            $headerFields[] = "Content-Type: application/json";
            $headerFields[] = "Expect:"; // disables 100 CONTINUE
            $ch = curl_init();
            $url = $this->moleculeRendererServiceUrl;
            $payload = new \stdClass();
            $payload->molfile = $molfile;
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
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                throw new Exception("Error on request: $error_msg HTTP-Code: $httpcode");
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

    public function check(): \CurlHandle|false
    {
        $benzolMolfile = <<<MOL

  -INDIGO-08042212082D

  0  0  0  0  0  0  0  0  0  0  0 V3000
M  V30 BEGIN CTAB
M  V30 COUNTS 6 6 0 0 0
M  V30 BEGIN ATOM
M  V30 1 C 1.25985 -4.72507 0.0 0
M  V30 2 C 2.99015 -4.72459 0.0 0
M  V30 3 C 2.12664 -4.22497 0.0 0
M  V30 4 C 2.99015 -5.72553 0.0 0
M  V30 5 C 1.25985 -5.73002 0.0 0
M  V30 6 C 2.12882 -6.22503 0.0 0
M  V30 END ATOM
M  V30 BEGIN BOND
M  V30 1 2 3 1
M  V30 2 2 4 2
M  V30 3 1 1 5
M  V30 4 1 2 3
M  V30 5 2 5 6
M  V30 6 1 6 4
M  V30 END BOND
M  V30 END CTAB
M  END

MOL;
        $headerFields = [];
        $headerFields[] = "Content-Type: application/json";
        $headerFields[] = "Expect:"; // disables 100 CONTINUE
        $ch = curl_init();
        $url = $this->moleculeRendererServiceUrl;
        $payload = new \stdClass();
        $payload->molfile = $benzolMolfile;
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