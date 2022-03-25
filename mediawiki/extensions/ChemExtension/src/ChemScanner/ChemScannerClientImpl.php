<?php

namespace DIQA\ChemExtension\ChemScanner;

use CURLFile;
use Exception;

class ChemScannerClientImpl implements ChemScannerClient {

    private $chemScannerBaseUrl;

    /**
     * @param $chemScannerBaseUrl
     */
    public function __construct($chemScannerBaseUrl = NULL)
    {
        if (is_null($chemScannerBaseUrl)) {
            global $wgCEChemScannerBaseUrl;
            $chemScannerBaseUrl = $wgCEChemScannerBaseUrl ?? null;
            if (is_null($chemScannerBaseUrl)) {
                throw new Exception('Chemscanner is not properly configured. Set $wgCEChemScannerBaseUrl.');
            }
        }
        $this->chemScannerBaseUrl = $chemScannerBaseUrl;
    }


    function uploadFile($documentPath)
    {
        $curlFile = new CURLFile($documentPath);
        $filename = basename($documentPath);

        try {
            $headerFields = [];
            $headerFields[] = "Content-Type: multipart/form-data";

            $ch = curl_init();
            $url = $this->chemScannerBaseUrl . "/api/v1/chemscanner/scan";
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,  [
                $filename => $curlFile,
            ]);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerFields );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                throw new Exception("Error on upload: " . $error_msg);
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
