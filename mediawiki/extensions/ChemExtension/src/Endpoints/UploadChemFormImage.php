<?php
namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Pages\InchIGenerator;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

class UploadChemFormImage extends SimpleHandler {

    public function run() {

        $params = $this->getValidatedParams();


        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );
        $chemFormRepo = new ChemFormRepository($dbr);
        return $chemFormRepo->addChemFormImage($params['id'], $params['imgData']);

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

            'imgData' => [
                self::PARAM_SOURCE => 'post',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],
        ];
    }
}