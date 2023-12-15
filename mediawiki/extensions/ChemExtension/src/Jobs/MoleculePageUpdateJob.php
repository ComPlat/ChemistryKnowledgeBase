<?php

namespace DIQA\ChemExtension\Jobs;

use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\TemplateEditor;
use DIQA\ChemExtension\Utils\WikiTools;
use Job;

class MoleculePageUpdateJob extends Job
{
    private $logger;
    private $molfile;
    private $moleculeKey;
    private $smiles;
    private $inchi;
    private $inchikey;

    public function __construct($title, $params)
    {
        parent::__construct('MoleculePageUpdateJob', $title, $params);
        $this->logger = new LoggerUtils('MoleculePageUpdateJob', 'ChemExtension');
        $this->molfile = $params['molOrRxn'];
        $this->moleculeKey = $params['moleculeKey'];
        $this->smiles = $params['smiles'];
        $this->inchi = $params['inchi'];
        $this->inchikey = $params['inchikey'];
    }

    public function run()
    {
        $this->logger->log("Updating Molecule/MoleculeCollection template of " . $this->title->getPrefixedText());
        $wikitext = WikiTools::getText($this->title);
        $te = new TemplateEditor($wikitext);
        $params = [
            'molOrRxn' => base64_decode($this->molfile),
            'moleculeKey' => $this->moleculeKey,
            'smiles' => $this->smiles,
            'inchi' => $this->inchi,
            'inchikey' => $this->inchikey,
        ];
        $this->logger->log("Parameters: " . print_r($params, true));
        $te->replaceTemplateParameters('Molecule', $params);
        $te->replaceTemplateParameters('MoleculeCollection', $params);
        WikiTools::doEditContent($this->title, $te->getWikiText(), "auto-generated", EDIT_UPDATE);
        $this->logger->log("done");
    }


}