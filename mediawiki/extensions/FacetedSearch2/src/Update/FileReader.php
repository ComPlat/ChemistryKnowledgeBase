<?php

namespace DIQA\FacetedSearch2\Update;

use DIQA\FacetedSearch2\ConfigTools;
use DIQA\FacetedSearch2\Model\Common\Datatype;
use DIQA\FacetedSearch2\Model\Common\Property;
use DIQA\FacetedSearch2\Model\Update\PropertyValues;
use Exception;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use WikiPage;

class FileReader {

    public function getTextFromFile(WikiPage $wikiPage, array &$doc, array &$messages): string
    {
        $pageTitle = $wikiPage->getTitle();
        $pageNamespace = $pageTitle->getNamespace();
        if ($pageNamespace !== NS_FILE) {
            return '';
        }


        $pageDbKey = $pageTitle->getDBkey();

        global $fs2gIndexImageURL;

        try {
            if (isset($fs2gIndexImageURL) && $fs2gIndexImageURL === true) {
                $this->retrieveFileSystemPath($pageNamespace, $pageDbKey, $doc);
            }
            $client = ConfigTools::getFacetedSearchClient();
            $metadata = $this->getDocumentMetadata($pageTitle);
            if (is_null($metadata)) return '';
            $text = $client->requestFileExtraction(file_get_contents($metadata['filePath']), $metadata['contentType']);

        } catch (Exception $e) {
            $messages[] = $e->getMessage();
            $text = $e->getMessage();
        }

        return $text;
    }


    /**
     * Retrieves full URL of the file resource attached to this title.
     *
     * @param int $namespace namespace-id
     * @param string $title dbkey
     * @param array $doc (out)
     */
    private function retrieveFileSystemPath($namespace, $title, array &$doc): void
    {
        $title = Title::newFromText($title, $namespace);
        $file = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo()->newFile($title);
        $filepath = $file->getFullUrl();

        $doc['smwh_properties'][] = new PropertyValues(new Property("Diqa import fullpath", Datatype::STRING), [$filepath]);
    }

    private function getDocumentMetadata($title): ?array
    {
        if ($title instanceof Title) {
            $file = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo()->newFile($title);
            $filepath = $file->getLocalRefPath();
        } else {
            $filepath = $title;
        }

        // get file and extension
        $ext = pathinfo($filepath, PATHINFO_EXTENSION);

        // choose content type
        if ($ext == 'pdf') {
            $contentType = 'application/pdf';
        } else if ($ext == 'doc' || $ext == 'docx') {
            $contentType = 'application/msword';
        } else if ($ext == 'ppt' || $ext == 'pptx') {
            $contentType = 'application/vnd.ms-powerpoint';
        } else if ($ext == 'xls' || $ext == 'xlsx') {
            $contentType = 'application/vnd.ms-excel';
        } else {
            // general binary data as fallback (don't know if Tika accepts it)
            $contentType = 'application/octet-stream';
        }

        // do not index unknown formats
        if ($contentType == 'application/octet-stream') {
            return null;
        }

        // send document to Tika and extract text
        if ($filepath == '') {
            if (PHP_SAPI === 'cli' && !defined('UNITTEST_MODE')) {
                throw new Exception(sprintf("\nWARN  Empty file path for '%s'. Can not index document properly.\n", $title->getPrefixedText()));
            }
            return null;
        }

        return ['filePath' => $filepath, 'contentType' => $contentType];
    }
}
