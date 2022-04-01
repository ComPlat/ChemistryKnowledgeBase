<?php

namespace DIQA\ChemExtension\ChemScanner;

use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use MediaWiki\MediaWikiServices;
use Philo\Blade\Blade;
use Title;

class ChemScannerRequest
{

    private $documentPath;
    private $blade;

    /**
     * @param $document
     */
    public function __construct($documentPath)
    {

        $this->documentPath = $documentPath;

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new Blade ($views, $cache);
    }

    public function send(): array
    {
        global $wgUser;
        $wgUser = \User::newFromName("WikiSysop");
        $userId = is_null($wgUser) ? NULL : $wgUser->getId();

        if (is_null($userId)) {
            throw new Exception("No user found");
        }
        if (!file_exists($this->documentPath) || !is_readable($this->documentPath)) {
            throw new Exception("File does not exist or is not readable: " . $this->documentPath);
        }

        global $wgCEChemScannerUseMock;
        $useMock = $wgCEChemScannerUseMock ?? false;
        $client = $useMock ? new ChemScannerClientMock() : new ChemScannerClientImpl();

        $response = $client->uploadFile($this->documentPath);


        $createdPages = [];
        foreach($response->files as $file) {

            $annotations = $this->getTemplateWithMetadata();
            $title = Title::newFromText($file->job_id);
            WikiTools::doEditContent($title,
                "$annotations",
                "auto-generated", EDIT_NEW);
            $store = MediaWikiServices::getInstance()->getWatchedItemStore();
            $store->addWatch($wgUser, $title);
            $createdPages[] = $file->job_id;
        }
        return $createdPages;
    }

    private function getTemplateWithMetadata() {
        global $wgUser;
        $contLang = MediaWikiServices::getInstance()->getContentLanguage();
        $userNsName = $contLang->getNsText(NS_USER);
        return '{{'.$this->blade->view ()->make ( "chemscanner-metadata",
            ['user' => $userNsName.":".$wgUser->getName(),
            ]
        )->render ();
    }
}