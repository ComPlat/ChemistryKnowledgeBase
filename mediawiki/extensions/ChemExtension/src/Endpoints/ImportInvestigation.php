<?php
namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Jobs\ImportInvestigationJob;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Title;
use LocalFile;

class ImportInvestigation extends SimpleHandler {

    public function run() {

        try {
            $jsonBody = $this->getRequest()->getBody();
            if (is_null($jsonBody) || $jsonBody == '') {
                throw new ValidationException("message body is empty");
            }
            $body = json_decode($jsonBody);
            $this->validateParameters($body);

            $investigationTitle = Title::newFromText($body->publicationPage . "/" . $body->selectedExperimentName);

            $fileTitle = Title::newFromText($body->filename, NS_FILE);
            if (!$fileTitle->exists()) {
                throw new Exception("'{$body->filename}' does not exist.");
            }
            $localRepo = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo();
            $file = LocalFile::newFromTitle($fileTitle, $localRepo);
            self::importData($investigationTitle, $file->getLocalRefPath(), $body->selectedExperiment);

            $res = new Response();
            $res->setStatus(204);
            return $res;
        } catch(ValidationException $e) {
            $res = new Response($e->getMessage());
            $res->setStatus(400);
            return $res;
        } catch(\Exception $e) {
            $res = new Response($e->getMessage());
            $res->setStatus(500);
            return $res;
        }
    }

    private static function importData(Title $investigationTitle, string $fullPath, string $investigationType)
    {
        $job = new ImportInvestigationJob($investigationTitle, [
            'filePath' => $fullPath,
            'investigationTitle' => $investigationTitle,
            'investigationType' => $investigationType
        ]);
        $jobQueue = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup();
        $jobQueue->push($job);
    }

    public function needsWriteAccess() {
        return false;
    }

    public function getParamSettings() {
        return [];
    }

    /**
     * @param $body
     */
    private function validateParameters($body): void
    {
        if (!isset($body->publicationPage)) {
            throw new ValidationException("publicationPage property is missing");

        }
        if (!isset($body->filename)) {
            throw new ValidationException("filename property is missing");

        }
        if (!isset($body->selectedExperiment)) {
            throw new ValidationException("selectedExperiment property is missing");

        }
        if (!isset($body->selectedExperimentName)) {
            throw new ValidationException("selectedExperimentName property is missing");

        }
    }
}