<?php

namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Experiments\ExperimentXlsExporter;
use Exception;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use SMW\ApplicationFactory;
use Title;
use Wikimedia\ParamValidator\ValidationException;

class GetExperimentAsXlsx extends SimpleHandler
{

    public function run()
    {
        try {
            $jsonBody = $this->getRequest()->getBody();
            $body = json_decode($jsonBody);
            $this->validateParams($body);

            $tmpFile = $this->saveSpreadSheetAsFile($body);
            $xlsxContent = file_get_contents($tmpFile);
            unlink($tmpFile);

            $res = new Response($xlsxContent);
            $res->addHeader('Content-Type', 'application/download');
            $title = Title::newFromText($body->page);
            $contentDisposition = sprintf('attachment; filename="%s_%s.xlsx"', $title->getPrefixedDBkey(), date("Ymd_His"));
            $res->addHeader('Content-Disposition', $contentDisposition);
            return $res;

        } catch (ValidationException $e) {
            $res = new Response($e->getMessage());
            $res->setStatus(400);
            return $res;
        } catch (Exception $e) {
            $res = new Response($e->getMessage());
            $res->setStatus($e->getCode() ?? 500);
            return $res;
        }
    }

    private function validateParams($body) {
        if (is_null($body)) {
            throw new ValidationException("message body is empty");
        }
        if (!isset($body->parameters)) {
            throw new ValidationException("parameters body is empty");
        }
        if (!isset($body->selectExperimentQuery)) {
            throw new ValidationException("selectExperimentQuery body is empty");
        }
        return $body;
    }

    public function needsWriteAccess()
    {
        return false;
    }

    /**
     * Saves the spreadsheet file to a temporary file.
     *
     * @param $jsonBody
     * @return string filename of temporary file
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    private function saveSpreadSheetAsFile($body): string
    {
        $spreadSheet = new Spreadsheet();
        $workSheet = $spreadSheet->getActiveSheet();

        $exporter = new ExperimentXlsExporter($body, $workSheet);
        $exporter->export();

        $writer = IOFactory::createWriter($spreadSheet, "Xlsx");
        $tmpFile = sys_get_temp_dir() . '/' . uniqid();
        $writer->save($tmpFile);
        return $tmpFile;
    }


}