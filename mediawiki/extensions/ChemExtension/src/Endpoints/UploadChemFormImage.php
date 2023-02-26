<?php
namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Pages\InchIGenerator;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

class UploadChemFormImage extends SimpleHandler {

    public function run() {

        $params = $this->getValidatedParams();

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );

        $chemFormRepo = new ChemFormRepository($dbr);
        if (!is_null($chemFormRepo->getChemFormId($params['moleculeKey']))) {
            $res = new Response();
            $res->setStatus(200);
            return $res;
        }

        $moleculeKeyOld = "reserved-" . $params['moleculeKeyToReplace'];
        $moleculeKeyNew = "reserved-" . $params['moleculeKey'];
        if (isset($params['moleculeKeyToReplace']) && !is_null($chemFormRepo->getChemFormImageByKey($moleculeKeyOld))) {
            $chemFormRepo->replaceChemFormImage($moleculeKeyOld, $moleculeKeyNew, $params['imgData']);
        } else {
            $chemFormRepo->addChemFormImage($moleculeKeyNew, $params['imgData']);
        }


        $res = new Response();
        $res->setStatus(200);
        return $res;
    }

    public function needsWriteAccess() {
        return false;
    }

    public function getParamSettings() {
        return [
            'moleculeKey' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],

            'imgData' => [
                self::PARAM_SOURCE => 'post',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],

            'moleculeKeyToReplace' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => false,
            ],

        ];
    }
}