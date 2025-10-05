<?php

namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Experiments\ExperimentEditor;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Title\Title;

class EditExperiment extends SimpleHandler
{

    public function run()
    {
        try {
            $jsonBody = $this->getRequest()->getBody();

            if (is_null($jsonBody) || trim($jsonBody->__toString()) === '') {
                $res = new Response("message body is empty");
                $res->setStatus(400);
                return $res;
            }
            $body = json_decode($jsonBody);
            if (!isset($body->investigationPageTitle) || !isset($body->investigationType) || !isset($body->changes)) {
                $res = new Response("investigationPageTitle, investigationType or changes is missing");
                $res->setStatus(400);
                return $res;
            }

            $investigationPageTitle = Title::newFromText($body->investigationPageTitle);
            if (!$investigationPageTitle->exists()) {
                $res = new Response("investigationPageTitle does not exist");
                $res->setStatus(400);
                return $res;
            }
            $changes = $body->changes;
            foreach ($changes as $change) {
                if (!isset($change->row) || !isset($change->property) || !isset($change->value)) {
                    $res = new Response("row, property or value is missing");
                    $res->setStatus(400);
                    return $res;
                }
            }

            $wikitext = WikiTools::getText($investigationPageTitle);
            $expEditor = new ExperimentEditor($wikitext, $body->investigationType);
            foreach ($changes as $change) {
                $expEditor->setValue($change->row, $change->property, $change->value);
            }
            //WikiTools::doEditContent($expPageTitle, $expEditor->serialize(), "auto-updated", EDIT_UPDATE);


            $res = new Response($expEditor->serialize());
            $res->setStatus(200);
            return $res;
        } catch (Exception $e) {
            $res = new Response($e->getMessage());
            $res->setStatus(500);
            return $res;
        }

    }

    public function needsWriteAccess()
    {
        return false;
    }

    public function getParamSettings()
    {
        return [];
    }
}