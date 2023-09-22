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
    private $moleculeKey;
    private $smiles;
    private $inchi;
    private $inchikey;

    public function __construct($title, $params)
    {
        parent::__construct('MolfileUpdateJob', $title, $params);
        $this->logger = new LoggerUtils('MolfileUpdateJob', 'ChemExtension');
        $this->molfile = $params['molOrRxn'];
        $this->moleculeKey = $params['moleculeKey'];
        $this->smiles = $params['smiles'];
        $this->inchi = $params['inchi'];
        $this->inchikey = $params['inchikey'];
    }

    public function run()
    {
        $this->logger->log("Updating molfile of " . $this->title->getPrefixedText());
        $wikitext = WikiTools::getText($this->title);
        $te = new TemplateEditor($wikitext);
        $wikitext = $te->replaceTemplateParameters('Molecule', [
            'molOrRxn' => base64_decode($this->molfile),
            'moleculeKey' => $this->moleculeKey,
            'smiles' => $this->smiles,
            'inchi' => $this->inchi,
            'inchikey' => $this->inchikey,
        ]);
        $wikitext = $te->replaceTemplateParameters('MoleculeCollection', [
            'molOrRxn' => base64_decode($this->molfile),
            'moleculeKey' => $this->moleculeKey,
            'smiles' => $this->smiles,
            'inchi' => $this->inchi,
            'inchikey' => $this->inchikey,
        ]);
        WikiTools::doEditContent($this->title, $wikitext, "auto-generated", EDIT_UPDATE);
        $this->logger->log("done");
    }


}