<?php
namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Pages\ChemFormRepository;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use Title;
use Wikimedia\ParamValidator\ParamValidator;

class GetRGroups extends SimpleHandler {

    public function run() {

        $params = $this->getValidatedParams();

        $key = $params['key'];
        $pageid = $params['pageid'];
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $chemFormRepo = new ChemFormRepository($dbr);

        $result = $chemFormRepo->getConcreteMoleculesByKey($key, Title::newFromId($pageid));
        return array_map(function($row) {
            $moleculePage = Title::newFromID($row['molecule_page_id']);
            return  [
                'publication_page_id' => $row['publication_page_id'],
                'molecule_page_name' => $moleculePage->getText(),
                'molecule_page_url' => $moleculePage->getFullURL(),
                'rests' => $row['rests'],

            ];
        }, $result);
    }

    public function needsWriteAccess() {
        return false;
    }

    public function getParamSettings() {
        return [

            'key' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],
            'pageid' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ]
        ];
    }
}