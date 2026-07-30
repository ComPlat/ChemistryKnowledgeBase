<?php
namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Utils\WikiTools;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;

class UpdateMoleculeInInvestigation extends SimpleHandler {

    public function run() {

        $jsonBody = $this->getRequest()->getBody();
        if ($jsonBody == '') {
            $res = new Response("message body is empty");
            $res->setStatus(400);
            return $res;
        }
        $body = json_decode($jsonBody);

        if (empty($body->moleculeAsText) || empty($body->inchiKey || empty($body->investigationPage))) {
            $res = new Response("Missing required parameters: moleculeAsText, inchiKey, investigationPage");
            $res->setStatus(400);
            return $res;
        }

        $moleculeAsText = $body->moleculeAsText;
        $moleculeAsText = str_replace(['&#65339;', '&#65341;'], ['[', ']'], $moleculeAsText); // un-sanitize, see SanitizeMolecule
        $inchiKey = $body->inchiKey;

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $repo = new ChemFormRepository($dbr);
        $moleculeId = $repo->getChemFormId($inchiKey);

        $text = WikiTools::getText($body->investigationPage);
        $text = preg_replace('/=\s*'.preg_quote($moleculeAsText).'\s*/', "=Molecule:$moleculeId\n", $text);
        WikiTools::doEditContent($body->investigationPage, $text, "auto-updated", EDIT_UPDATE);

        $res = new Response();
        $res->setStatus(200);
        return $res;
    }

    public function needsWriteAccess() {
        return true;
    }

    public function getParamSettings() {
        return [];
    }
}