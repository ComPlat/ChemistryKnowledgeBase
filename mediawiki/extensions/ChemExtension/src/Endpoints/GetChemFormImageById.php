<?php

namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Pages\InchIGenerator;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

class GetChemFormImageById extends SimpleHandler
{

    public function run()
    {

        $params = $this->getValidatedParams();

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);

        $chemFormRepo = new ChemFormRepository($dbr);
        $chemFormId = $params['chemFormId'];
        $imgDataBase64 = $chemFormRepo->getChemFormImageById($chemFormId);
        if (is_null($imgDataBase64) || $imgDataBase64 == '') {
            $res = new Response("chemical formula does not exist: $chemFormId");
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
            'chemFormId' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],

        ];
    }
}