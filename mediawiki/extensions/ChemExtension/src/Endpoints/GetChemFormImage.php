<?php

namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Pages\InchIGenerator;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

class GetChemFormImage extends SimpleHandler
{

    public function run()
    {

        $params = $this->getValidatedParams();

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);

        $chemFormRepo = new ChemFormRepository($dbr);
        $moleculeKey = $params['moleculeKey'];
        $imgDataBase64 = $chemFormRepo->getChemFormImageByKey("reserved-".$moleculeKey);
        if (is_null($imgDataBase64)) {
            $imgDataBase64 = $chemFormRepo->getChemFormImageByKey($moleculeKey);
        }
        if (is_null($imgDataBase64) || $imgDataBase64 == '') {
            $res = new Response("chemical formula does not exist: $moleculeKey");
            $res->setStatus(400);
            return $res;
        }
        $imgData = base64_decode($imgDataBase64);

        $response = new Response($imgData);
        $response->setHeader('Content-Type', 'image/svg+xml');
        return $response;
    }

    public function needsWriteAccess()
    {
        return false;
    }

    public function getParamSettings()
    {
        return [
            'moleculeKey' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],

        ];
    }
}