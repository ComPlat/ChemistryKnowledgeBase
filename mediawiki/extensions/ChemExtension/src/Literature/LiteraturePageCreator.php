<?php

namespace DIQA\ChemExtension\Literature;

use DIQA\ChemExtension\Utils\LoggerUtils;

class LiteraturePageCreator {

    private $logger;

    public function __construct() {
        $this->logger = new LoggerUtils('LiteraturePageCreator', 'ChemExtension');
    }

    public function createPage($doi, $doiData) {
        $this->logger->log("TODO: Create page with DOI: $doi");
    }
}