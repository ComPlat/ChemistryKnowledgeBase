<?php
namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Pages\MolfileUpdateJob;
use JobQueueGroup;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Title;
use Wikimedia\ParamValidator\ParamValidator;

class ReplaceChemFormImage extends SimpleHandler {

    public function run() {

        global $wgUser;
        if (!MediaWikiServices::getInstance()
            ->getPermissionManager()
            ->userHasRight( $wgUser, 'delete' )) {
            $res = new Response();
            $res->setStatus(403);
            return $res;
        }

        $params = $this->getValidatedParams();

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );

        $chemFormRepo = new ChemFormRepository($dbr);
        $chemFormId = $chemFormRepo->getChemFormId($params['moleculeKey']);

        if (!is_null($chemFormId) && $chemFormId === $params['chemFormId']) {
            $chemFormRepo->addOrUpdateChemFormImage($params['moleculeKey'], base64_encode($params['imgData']));
            $job = new MolfileUpdateJob(Title::newFromText($params['chemFormId'], NS_MOLECULE), $params);
            JobQueueGroup::singleton()->push( $job );
            $res = new Response();
            $res->setStatus(200);
            return $res;
        }


        $res = new Response();
        $res->setStatus(400);
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

            'chemFormId' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],

            'imgData' => [
                self::PARAM_SOURCE => 'post',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],
            'molOrRxn' => [
                self::PARAM_SOURCE => 'post',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],
        ];
    }
}