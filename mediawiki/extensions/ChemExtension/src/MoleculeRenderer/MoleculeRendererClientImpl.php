<?php
namespace DIQA\ChemExtension\MoleculeRenderer;

use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;

class MoleculeRendererClientImpl {
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

    protected static function splitResponse($res): array
    {
        $bodyBegin = strpos($res, "\r\n\r\n");
        list($header, $res) = $bodyBegin !== false ? array(substr($res, 0, $bodyBegin), substr($res, $bodyBegin + 4)) : array($res, "");
        return array($header, str_replace("%0A%0D%0A%0D", "\r\n\r\n", $res));
    }
}