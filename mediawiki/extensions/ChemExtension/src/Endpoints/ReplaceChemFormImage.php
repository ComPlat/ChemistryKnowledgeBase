<?php
namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Pages\ChemForm;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Pages\MolfileUpdateJob;
use DIQA\ChemExtension\Specials\ReplaceMoleculeJob;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use RequestContext;
use Title;
use Wikimedia\ParamValidator\ParamValidator;

class ReplaceChemFormImage extends SimpleHandler {

    public function run() {

        if (!in_array('editor', MediaWikiServices::getInstance()
            ->getUserGroupManager()
            ->getUserGroups( RequestContext::getMain()->getUser()))) {
            $res = new Response();
            $res->setStatus(403);
            return $res;
        }

        $jobQueue = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup();
        $params = $this->getValidatedParams();

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);

        $chemFormRepo = new ChemFormRepository($dbr);
        $chemFormId = $chemFormRepo->getChemFormId($params['moleculeKey']);

        if (!is_null($chemFormId) && $chemFormId === $params['chemFormId']) {
            // moleculeKey did not change, still refers to the old molecule. only image changed.
            $chemFormRepo->addOrUpdateChemFormImage($params['moleculeKey'], base64_encode($params['imgData']));
            $job = new MolfileUpdateJob(Title::newFromText($params['chemFormId'], NS_MOLECULE), $params);
            $jobQueue->push( $job );
            $res = new Response();
            $res->setStatus(200);
            return $res;
        } else if (is_null($chemFormId)) {
            // moleculeKey has changed, ie. chemFormId must be updated with a new moleculeKey and image

            $chemFormRepo->updateImageAndMoleculeKey($params['chemFormId'], $params['moleculeKey'],
                base64_encode($params['imgData']));
            $chemForm = ChemForm::fromMolOrRxn(base64_decode($params['molOrRxn']), $params['smiles'], $params['inchi'], $params['inchikey']);
            $title = Title::newFromText($params['chemFormId'], NS_MOLECULE);
            $params['chemform'] = $chemForm;
            $job = new ReplaceMoleculeJob($title, $params);
            $jobQueue->push( $job );
            $job = new MolfileUpdateJob($title, $params);
            $jobQueue->push( $job );
            $res = new Response();
            $res->setStatus(200);
            return $res;
        } else {
            // new molecule already exists
            $res = new Response("Molecule with molecule_key '". $params['moleculeKey'] . "' already exists");
            $res->setStatus(400);
            return $res;
        }

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
            'oldMoleculeKey' => [
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
            'smiles' => [
                self::PARAM_SOURCE => 'post',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => false,
            ],
            'inchi' => [
                self::PARAM_SOURCE => 'post',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => false,
            ],
            'inchikey' => [
                self::PARAM_SOURCE => 'post',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => false,
            ],
        ];
    }
}