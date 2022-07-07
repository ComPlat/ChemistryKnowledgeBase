<?php
namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Pages\InchIGenerator;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

class GetChemFormImage extends SimpleHandler {

    public function run() {

        $params = $this->getValidatedParams();

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );

        $chemFormRepo = new ChemFormRepository($dbr);
        $inchikey = $params['id'];
        $chemFormImage64 = $chemFormRepo->getChemFormImage($inchikey);
        if (is_null($chemFormImage64)) {
            $res = new Response("chemical formula does not exist: $inchikey");
            $res->setStatus(400);
            return $res;
        }
        $chemFormImage = base64_decode($chemFormImage64);

        $response = new Response($chemFormImage);
        $response->setHeader('Content-Type', 'image/svg+xml');
        return $response;
    }

    public function needsWriteAccess() {
        return false;
    }

    public function getParamSettings() {
        return [
            'id' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],

        ];
    }
}