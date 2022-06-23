<?php
namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Pages\InchIGenerator;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

class GetInchI extends SimpleHandler {

    public function run() {

        $params = $this->getValidatedParams();

        $mol = base64_decode($params['mol']);

        $inchiGenerator = new InchIGenerator();
        $inchi = $inchiGenerator->getInchI($mol);

        return $inchi;
    }

    public function needsWriteAccess() {
        return false;
    }

    public function getParamSettings() {
        return [

            'mol' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ]
        ];
    }
}