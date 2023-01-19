<?php

namespace DIQA\ChemExtension\Literature;

use DIQA\ChemExtension\ParserFunctions\RenderLiterature;
use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;

class LiteratureResolverJob extends \Job {

    private $logger;
    private $doi;

    public function __construct($title, $params)
    {
        parent::__construct('LiteratureResolverJob', $title, $params);
        $this->logger = new LoggerUtils('LiteratureResolverJob', 'ChemExtension');
        $this->doi = $params['doi'];

    }

    public function run()
    {
        try {
            $doiResolver = new DOIResolver();
            $doiResolver->resolve($this->doi);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}