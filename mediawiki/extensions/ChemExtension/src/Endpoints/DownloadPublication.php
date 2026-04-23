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
            $jobQueue = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup();
            $crossRefApi = new CrossRefApi();
            $pdfDownloads = $crossRefApi->findPdfDownloads($doi);
            if (count($pdfDownloads) > 0) {
                $first = reset($pdfDownloads);
                $jobQueue->push(new DownloadPDFJob(Title::newFromText("DownloadPublication"), [
                    'url' => $first->URL,
                    'doi' => $doi,
                ]));
                $res = new Response($first->URL);
            } else {
                $downloader = new DownloadLinkFinder('https://doi.org/' . $doi);
                $links = $downloader->findDownloadLinks();
                if (empty($links)) {
                    throw new Exception("no download links found", 404);
                }
                $jobQueue->push(new DownloadPDFJob(Title::newFromText("DownloadPublication"), [
                    'url' => $links[0]['url'],
                    'doi' => $doi,
                ]));
                $res = new Response($links[0]['url']);
            }
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

}