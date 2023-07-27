<?php

namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Utils\JSONLD\JSONLDSerializer;
use DIQA\ChemExtension\Utils\NQuadProducer;
use DIQA\ChemExtension\Utils\QueryUtils;
use Exception;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use ML\JsonLD\JsonLD;
use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use Title;
use Wikimedia\ParamValidator\ParamValidator;
use SMWExporter;

class GetJSONLD extends SimpleHandler
{

    private $NQuadProducer;

    public function __construct()
    {
        $this->NQuadProducer = new NQuadProducer();
    }

    public function run()
    {
        try {
            $params = $this->getValidatedParams();
            $title = Title::newFromText($params['page']);
            if (is_null($title) || !$title->isValid()) {
                throw new Exception("'page' parameter is invalid. Must be a wiki page title", 400);
            }
            if (!$title->exists()) {
                throw new Exception("page does not exist", 400);
            }
            $pageAsDataItem = new DIWikiPage($title->getDBkey(), $title->getNamespace(), $title->getInterwiki());
            $this->serializeSubject($pageAsDataItem);
            $subObjects = QueryUtils::getPropertyValues($title, DIProperty::TYPE_SUBOBJECT);

            foreach ($subObjects as $subObject) {
                $this->serializeSubject($subObject);
            }
            $jsonLD = JsonLD::fromRdf($this->NQuadProducer->getQuads());
            $res = new Response(JsonLD::toString($jsonLD, true));
            $res->addHeader('Content-Type', 'application/json');
            $contentDisposition = sprintf('attachment; filename="%s_%s.json"', $title->getPrefixedDBkey(), date("Ymd_His"));
            $res->addHeader('Content-Disposition', $contentDisposition);
            return $res;

        } catch (Exception $e) {
            $res = new Response($e->getMessage());
            $res->setStatus($e->getCode() ?? 500);
            return $res;
        }
    }

    private function serializeSubject(DIWikiPage $dataItem): void
    {
        $applicationFactory = ApplicationFactory::getInstance();
        $semanticData = $applicationFactory->getStore()->getSemanticData($dataItem);
        $expData = SMWExporter::getInstance()->makeExportData($semanticData);
        $this->NQuadProducer->serializeExpData($expData);
    }

    public function needsWriteAccess()
    {
        return false;
    }

    public function getParamSettings()
    {
        return [

            'page' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ]
        ];
    }

}