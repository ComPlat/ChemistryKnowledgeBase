<?php

namespace DIQA\ChemExtension\PublicationImport;

class DOIAlreadyExistsException extends \Exception {

    private string $doi;

    public function __construct($doi)
    {
        parent::__construct('DOI already exists');
        $this->doi = $doi;
    }

    public function getDOI(): string
    {
        return $this->doi;
    }
}