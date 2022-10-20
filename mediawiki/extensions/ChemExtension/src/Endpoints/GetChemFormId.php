<?php

namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Pages\InchIGenerator;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

class GetChemFormId extends SimpleHandler
{

    public function run()
    {

        $params = $this->getValidatedParams();

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);

        $chemFormRepo = new ChemFormRepository($dbr);
        $moleculeKey = $params['moleculeKey'];
        if (preg_match('/^\d+$/', $params['moleculeKey'], $matches) === 1) {
            $chemFormId = $params['moleculeKey'];
        } else {
            $chemFormId = $chemFormRepo->getChemFormId($moleculeKey);
        }
        if (is_null($chemFormId) || $chemFormId == '') {
            $res = new Response("chemical formula does not exist: $moleculeKey");
            $res->setStatus(400);
            return $res;
        }
        return ['chemformid' => $chemFormId];
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