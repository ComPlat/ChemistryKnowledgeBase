<?php

namespace DIQA\ChemExtension\ChemScanner;

use CURLFile;
use DIQA\ChemExtension\Utils\CurlUtil;
use Exception;

class ChemScannerClientImpl implements ChemScannerClient {

    private $chemScannerBaseUrl;
    private $chemScannerNotifyBaseUrl;

    /**
     *
     */
    public function __construct()
    {
        global $wgCEChemScannerBaseUrl;
        $chemScannerBaseUrl = $wgCEChemScannerBaseUrl ?? null;
        if (is_null($chemScannerBaseUrl)) {
            throw new Exception('Chemscanner is not properly configured. Set $wgCEChemScannerBaseUrl.');
        }

        global $wgServer, $wgScriptPath;
        global $wgCEChemScannerNotifyBaseUrl;
        $chemScannerNotifyBaseUrl = $wgCEChemScannerNotifyBaseUrl ?? $wgServer . $wgScriptPath;

        $this->chemScannerBaseUrl = $chemScannerBaseUrl;
        $this->chemScannerNotifyBaseUrl = $chemScannerNotifyBaseUrl;
    }


    function uploadFile($documentPath)
    {
        $curlFile = new CURLFile($documentPath);
        $filename = basename($documentPath);

        try {
            $headerFields = [];
            $headerFields[] = "Content-Type: multipart/form-data";
            $headerFields[] = "Expect:"; // disables 100 CONTINUE
            $ch = curl_init();
            $url = $this->chemScannerBaseUrl . "/api/v1/chemscanner/scan";
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,  [
                $filename => $curlFile,
                "postback_url" => $this->chemScannerNotifyBaseUrl . "/rest.php/ChemExtension/v1/chemscanner/jobs/notify-done"
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

            list($header, $body) = CurlUtil::splitResponse($response);
            if ($httpcode >= 200 && $httpcode <= 299) {
                return json_decode($body);
            }
            throw new Exception("Error on upload. HTTP status " . $httpcode . ". Message: " . $body);

        } finally {
            curl_close($ch);
        }
    }

}
