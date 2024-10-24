<?php
namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Pages\InchIGenerator;
use DIQA\ChemExtension\PubChem\PubChemRepository;
use DIQA\ChemExtension\PubChem\PubChemService;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;
use Exception;

class GetSMILESFromPubChem extends SimpleHandler {

    public function run() {

        $params = $this->getValidatedParams();

        $inchikey = $params['inchikey'];

        try {
            $service = new PubChemService();
            $data = $service->getPubChem($inchikey);

            $res = new Response($data['record']->getSMILES());
            $res->setStatus(200);
            return $res;

        } catch(Exception $e) {
            $res = new Response($e->getMessage());
            $res->setStatus(500);
            return $res;
        }
    }

    public function needsWriteAccess() {
        return false;
    }

    public function getParamSettings() {
        return [

            'inchikey' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ]
        ];
    }
}