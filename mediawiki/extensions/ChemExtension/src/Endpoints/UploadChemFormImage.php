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
        $chemFormId = $chemFormRepo->getChemFormId($params['moleculeKey']);
        $chemFormImage = $chemFormRepo->getChemFormImageByKey($params['moleculeKey']);
        if (!is_null($chemFormId) && $chemFormImage !== '') {
            // molecule exists and has image, don't touch it
            $res = new Response();
            $res->setStatus(200);
            return $res;
        }

        if (!is_null($chemFormId) && $chemFormImage === '') {
            // molecule exists and has no image, probably was just not rendered
            // happens when you copy a molecule as wikitext to a page
            $chemFormRepo->addOrUpdateChemFormImage($params['moleculeKey'], $params['imgData']);

        } else {
            // add or replace a "reserved" molecule image. this can be overwritten until the page is saved
            $moleculeKeyOld = "reserved-" . ($params['moleculeKeyToReplace'] ?? '');
            $moleculeKeyNew = "reserved-" . $params['moleculeKey'];
            if (isset($params['moleculeKeyToReplace']) && !is_null($chemFormRepo->getChemFormImageByKey($moleculeKeyOld))) {
                $chemFormRepo->replaceMoleculeKeyAndImage($moleculeKeyOld, $moleculeKeyNew, $params['imgData']);
            } else {
                $chemFormRepo->addOrUpdateChemFormImage($moleculeKeyNew, $params['imgData']);
            }
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