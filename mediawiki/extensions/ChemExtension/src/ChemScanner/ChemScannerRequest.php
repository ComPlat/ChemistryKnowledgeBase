<?php

namespace DIQA\ChemExtension\ChemScanner;
use CURLFile;
use DIQA\ChemExtension\Utils\WikiTools;
use MediaWiki\MediaWikiServices;
use Title;

class ChemScannerRequest {

    const MOCK = true;

    private $documentPath;

    /**
     * @param $document
     */
    public function __construct($documentPath)
    {

        $this->documentPath = $documentPath;
    }

    public function send() {
        global $wgUser;
        $wgUser = \User::newFromId(1);
        $userId = is_null($wgUser) ? NULL : $wgUser->getId();

        if (is_null($userId)) {
            throw new Exception("User must be logged in");
        }
        if (!file_exists($this->documentPath) || !is_readable($this->documentPath)) {
            throw new Exception("File does not exist or is not readable: " . $this->documentPath);
        }
        $response = self::MOCK ? $this->mockUpload() : $this->uploadFile();
        $filename = basename($this->documentPath);
        $title = Title::newFromText($filename);
        WikiTools::doEditContent($title, "--no be filled by ChemScannerJob --", "auto-generated", EDIT_NEW);
        $store = MediaWikiServices::getInstance()->getWatchedItemStore();
        $store->addWatch($wgUser, $title);
    }

    private function uploadFile() {
        $curlFile = new CURLFile($this->documentPath);
        $post = [
            'forced' => true,
            'sample' => $curlFile,
        ];

        try {

            $ch = curl_init();
            $url = "/some/upload-url";
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                throw new Exception("Error on upload: " . $error_msg);
            }
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpcode >= 200 && $httpcode <= 299) {
                return json_decode($result);
            }
            throw new Exception("Error on upload. HTTP status " . $httpcode . ". Message: " . $result);

        } finally {
            curl_close($ch);
        }
    }

    private function mockUpload() {
        $res = new \stdClass();
        $res->job_id = uniqid();
        return $res;
    }


}