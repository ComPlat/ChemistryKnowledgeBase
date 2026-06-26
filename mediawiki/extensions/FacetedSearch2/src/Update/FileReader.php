<?php

namespace DIQA\FacetedSearch2\Update;


use DIQA\FacetedSearch2\TextExtractors\PPTExtractor;
use DIQA\FacetedSearch2\TextExtractors\WordExtractor;
use DIQA\FacetedSearch2\TextExtractors\XLSExtractor;
use DIQA\FacetedSearch2\Model\Common\Datatype;
use DIQA\FacetedSearch2\Model\Common\Property;
use DIQA\FacetedSearch2\Model\Update\PropertyValues;
use DIQA\FacetedSearch2\Utils\ConfusableCharacterNormalizer;
use Exception;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use Smalot\PdfParser\Parser;
use WikiPage;

class FileReader
{

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

            $metadata = $this->getDocumentMetadata($pageTitle);
            if (is_null($metadata)) return '';
            $text = $this->extractText($metadata);


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
        } else if ($ext == 'doc') {
            $contentType = 'application/msword';
        } else if ($ext == 'docx') {
            $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        } else if ($ext == 'ppt') {
            $contentType = 'application/vnd.ms-powerpoint';
        } else if ($ext == 'pptx') {
            $contentType = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
        } else if ($ext == 'xls') {
            $contentType = 'application/vnd.ms-excel';
        } else if ($ext == 'xlsx') {
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } else {
            // general binary data as fallback (don't know if Tika accepts it)
            $contentType = 'application/octet-stream';
        }

        return ['filePath' => $filepath, 'contentType' => $contentType, 'ext' => $ext];
    }

    /**
     * @param array $metadata
     * @return string
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function extractText(array $metadata): string
    {
        $mediaExtractor = new WordExtractor();
        $excelExtractor = new XLSExtractor();
        $powerPointExtractor = new PPTExtractor();
        switch ($metadata['contentType']) {
            case 'application/pdf':
                try {
                    $parser = new Parser();
                    $content = file_get_contents($metadata['filePath']);
                    $pdf = $parser->parseContent($content);
                    $text = $pdf->getText();
                } catch (Exception $e) {
                    $text = "Could not extract PDF content due to: " . $e->getMessage();
                }
                break;
            case 'application/msword':
                $text = $mediaExtractor->extractDocument($metadata['filePath'], 'MsDoc');
                break;
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                $text = $mediaExtractor->extractDocument($metadata['filePath'], 'Word2007');
                break;
            case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
                $text = $excelExtractor->extractXlsxText($metadata['filePath']);
                break;
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                $text = $powerPointExtractor->extractPptxTextViaLib($metadata['filePath']);
                break;

            default:
                $text = '';
                break;
        }
        return ConfusableCharacterNormalizer::normalize($text);
    }
}
