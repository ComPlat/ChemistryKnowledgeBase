<?php

namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Jobs\DownloadPDFJob;
use DIQA\ChemExtension\PublicationSearch\CrossRefAPI;
use DIQA\ChemExtension\PublicationSearch\DownloadLinkFinder;
use Exception;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Title\Title;
use Wikimedia\ParamValidator\ParamValidator;

class DownloadPublication extends SimpleHandler
{

    public function run()
    {
        try {
            $params = $this->getValidatedParams();
            $doi = $params['doi'];
            if (is_null($doi)) {
                throw new Exception("'doi' parameter is missing", 400);
            }
            $url = self::downloadByDOI($doi);
            $res = new Response($url);
            $res->setStatus(200);
            return $res;

        } catch (Exception $e) {
            $res = new Response($e->getMessage());
            $res->setStatus($e->getCode() ?? 500);
            return $res;
        }
    }

    public function needsWriteAccess()
    {
        return false;
    }

    public function getParamSettings()
    {
        return [

            'doi' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ]
        ];
    }

    /**
     * @param mixed $doi
     * @return string URL of the first PDF download
     * @throws Exception
     */
    public static function downloadByDOI(mixed $doi): string
    {
        $jobQueue = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup();
        $crossRefApi = new CrossRefApi();
        $pdfDownloads = $crossRefApi->findPdfDownloads($doi);
        if (count($pdfDownloads) > 0) {
            $first = reset($pdfDownloads);
            $downloader = new DownloadLinkFinder($first->URL);
            try {
                $links = $downloader->findDownloadLinks(['pdf']);
                $url = $links[0]['url'];
            } catch(Exception $e) {
                $url = $first->URL;
            }
            $jobQueue->push(new DownloadPDFJob(Title::newFromText("DownloadPublication"), [
                'url' => $url,
                'doi' => $doi,
                'openExternally' => true
            ]));
            self::createJobsForSupplementaryPDFs($links ?? [], $doi);
        } else {
            $downloader = new DownloadLinkFinder('https://doi.org/' . $doi);
            try {
                $links = $downloader->findDownloadLinks(['pdf']);
                $url = $links[0]['url'];
            } catch(Exception $e) {
                throw new Exception("No PDF download link found for DOI: " . $doi);
            }
            $jobQueue->push(new DownloadPDFJob(Title::newFromText("DownloadPublication"), [
                'url' => $url,
                'doi' => $doi,
                'openExternally' => true
            ]));
            self::createJobsForSupplementaryPDFs($links, $doi);
        }
        return $url;
    }

    private static function createJobsForSupplementaryPDFs($links, $doi): void
    {

        $jobQueue = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup();
        foreach ($links as $link) {
            $text = strtolower($link['text']);
            if (str_contains($text, 'supplementary information')) {

                $jobQueue->push(new DownloadPDFJob(Title::newFromText("DownloadPublication"), [
                    'url' => $link['url'],
                    'doi' => $doi,
                    'openExternally' => true
                ]));
            }
        }

    }
}