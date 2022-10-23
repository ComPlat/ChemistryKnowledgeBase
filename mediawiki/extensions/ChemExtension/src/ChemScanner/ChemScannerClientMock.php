<?php

namespace DIQA\ChemExtension\ChemScanner;

class ChemScannerClientMock implements ChemScannerClient {

    function uploadFile($filePath)
    {
        $res = new \stdClass();

        $job = new \stdClass();
        $job->job_id = uniqid();
        $job->file = 'document123.docx';

        $res->files = [$job];

        return $res;
    }
}