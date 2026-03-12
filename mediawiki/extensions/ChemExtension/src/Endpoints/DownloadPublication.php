<?php

namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\PublicationSearch\DownloadLinkFinder;
use DIQA\ChemExtension\Utils\JSONLD\JSONLDSerializer;
use Exception;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

class DownloadPublication extends SimpleHandler
{

    public function run()
    {
        try {
            $params = $this->getValidatedParams();
            $doiUrl = $params['doi'];
            if (is_null($doiUrl)) {
                throw new Exception("'doi' parameter is missing", 400);
            }
            $downloader = new DownloadLinkFinder($doiUrl);
            $links = $downloader->findDownloadLinks();
            if (empty($links)) {
                throw new Exception("no download links found", 404);
            }
            $res = new Response($links[0]['url']);
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