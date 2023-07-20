<?php

namespace DIQA\ChemExtension\Pages;

use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\TemplateEditor;
use DIQA\ChemExtension\Utils\WikiTools;
use Job;

class MolfileUpdateJob extends Job
{
    private $logger;
    private $molfile;

    public function __construct($title, $params)
    {
        parent::__construct('MolfileUpdateJob', $title, $params);
        $this->logger = new LoggerUtils('MolfileUpdateJob', 'ChemExtension');
        $this->molfile = $params['molOrRxn'];

    }

    public function run()
    {
        $this->logger->log("Updating molfile of " . $this->title->getPrefixedText());
        $wikitext = WikiTools::getText($this->title);
        $te = new TemplateEditor($wikitext);
        $wikitext = $te->replaceTemplateParameters('Molecule', ['molOrRxn' => base64_decode($this->molfile) ]);
        WikiTools::doEditContent($this->title, $wikitext, "auto-generated", EDIT_UPDATE);
        $this->logger->log("done");
    }


}