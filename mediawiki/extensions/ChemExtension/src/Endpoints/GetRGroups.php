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

        $moleculeKey = $params['moleculekey'];
        $pageId = $params['pageid'];
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $chemFormRepo = new ChemFormRepository($dbr);

        $result = $chemFormRepo->getConcreteMoleculesByKey($moleculeKey, Title::newFromId($pageId));
        return array_map(function($row) use ($chemFormRepo) {
            $moleculePage = Title::newFromID($row['molecule_page_id']);
            $chemFormId = $this->getChemFormIdFromTitle($moleculePage);
            $moleculeKey = $chemFormRepo->getMoleculeKey($chemFormId);
            return  [
                'publication_page_id' => $row['publication_page_id'],
                'molecule_page_name' => $moleculePage->getText(),
                'molecule_page_url' => $moleculePage->getFullURL(),
                'moleculeKey' => $moleculeKey,
                'rGroups' => $row['rGroups'],

            ];
        }, $result);
    }

    private function getChemFormIdFromTitle(Title $title) {
        preg_match('/\d+/', $title->getDBkey(), $matches);
        return $matches[0];
    }

    public function needsWriteAccess() {
        return false;
    }

    public function getParamSettings() {
        return [

            'moleculekey' => [
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