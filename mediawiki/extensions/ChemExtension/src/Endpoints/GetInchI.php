<?php
namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Pages\InchIGenerator;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;
use Exception;

class GetInchI extends SimpleHandler {

    public function run() {

        $params = $this->getValidatedParams();

        $mol = base64_decode($params['mol']);

        try {

            $inchIGenerator = new InchIGenerator();
            return $inchIGenerator->getInchI($mol);

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

            'mol' => [
                self::PARAM_SOURCE => 'post',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ]
        ];
    }
}