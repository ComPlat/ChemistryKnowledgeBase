<?php
namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Pages\ChemForm;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Jobs\MoleculePageUpdateJob;
use DIQA\ChemExtension\Jobs\AdjustMoleculeReferencesJob;
use Job;
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
        $targetChemFormId = $chemFormRepo->getChemFormId($params['moleculeKey']);

        if (!is_null($targetChemFormId) && $targetChemFormId === $params['chemFormId']) {
            // moleculeKey did not change, still refers to the old molecule. only image changed.
            $chemFormRepo->addOrUpdateChemFormImage($params['moleculeKey'], base64_encode($params['imgData']));
            $job = new MoleculePageUpdateJob(Title::newFromText($params['chemFormId'], NS_MOLECULE), $params);
            $jobQueue->push( $job );
            $res = new Response('Molecule key did not change, image is updated');
            $res->setStatus(200);
            return $res;
        } else {

            $chemForm = ChemForm::fromMolOrRxn(
                base64_decode($params['molOrRxn']),
                $params['smiles'],
                $params['inchi'],
                $params['inchikey']);

            if (is_null($targetChemFormId)) {

                // moleculeKey has changed to a non-existing molecule, ie. chemFormId can remain the same
                // but must be updated with the new moleculeKey and image
                // this especially means chemFormIds do not have to be changed

                $chemFormRepo->updateImageAndMoleculeKey($params['chemFormId'], $params['moleculeKey'],
                    base64_encode($params['imgData']));
                $job = $this->createJobForReplacingMolecule($params['chemFormId'], $params['oldMoleculeKey'], null, $chemForm);
                $jobQueue->push($job);

                $job = new MoleculePageUpdateJob(Title::newFromText($params['chemFormId'], NS_MOLECULE), $params);
                $jobQueue->push($job);
                $message = "Molecule key changed, references are adapted";

            } else {

                // new molecule already exists, so change all references from the old to the new.
                // This especially means chemFormIds also have to changed to the new.
                // old molecule remains unchanged

                $job = $this->createJobForReplacingMolecule($params['chemFormId'], $params['oldMoleculeKey'], $targetChemFormId, $chemForm);
                $jobQueue->push($job);
                $message = "Molecule already exists, references are adapted including chemFormIds";
            }

            $res = new Response($message);
            $res->setStatus(200);
            return $res;
        }

    }

    private function createJobForReplacingMolecule($oldChemFormId, $oldMoleculeKey, $targetChemFormId, ChemForm $chemForm): Job
    {

        $paramsJob = [];
        $paramsJob['targetChemForm'] = $chemForm;
        $paramsJob['targetChemFormId'] = $targetChemFormId;
        $paramsJob['oldChemFormId'] = $oldChemFormId;
        $paramsJob['oldMoleculeKey'] = $oldMoleculeKey;
        $paramsJob['replaceChemFormId'] = !is_null($targetChemFormId);
        $title = Title::newFromText($oldChemFormId, NS_MOLECULE);
        return new AdjustMoleculeReferencesJob($title, $paramsJob);
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