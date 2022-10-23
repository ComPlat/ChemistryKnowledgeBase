<?php

namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Pages\InchIGenerator;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

class GetMoleculeKey extends SimpleHandler
{

    public function run()
    {

        $params = $this->getValidatedParams();

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);

        $chemFormRepo = new ChemFormRepository($dbr);
        $chemFormId = $params['chemformid'];
        $moleculeKey = $chemFormRepo->getMoleculeKey($chemFormId);

        if (is_null($moleculeKey) || $moleculeKey == '') {
            $res = new Response("chemical formula does not exist: $chemFormId");
            $res->setStatus(400);
            return $res;
        }
        return ['moleculeKey' => $moleculeKey];
    }

    public function needsWriteAccess()
    {
        return false;
    }

    public function getParamSettings()
    {
        return [
            'chemformid' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],

        ];
    }
}