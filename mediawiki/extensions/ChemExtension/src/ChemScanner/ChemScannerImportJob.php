<?php

namespace DIQA\ChemExtension\Chemscanner;


use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\TemplateEditor;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use Job;

class ChemScannerImportJob extends Job {

    private $jobId;
    private $body;
    private $logger;

    public function __construct( $title, $params ) {
        parent::__construct( 'ChemScannerImportJob', $title, $params );
        $this->jobId = $params['job_id'];
        $this->body = $params['body'];
        $this->logger = new LoggerUtils('ChemScannerImportJob', 'ChemExtension');
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        try {

            $chemScannerResult = json_decode($this->body);
            $this->importChemScannerResult($chemScannerResult);

            WikiTools::createNotificationJobs($this->getTitle());

        } catch (Exception $e) {
            $this->logger->error("ERROR: " . $e->getMessage());

        }
    }

    private function importChemScannerResult($chemScannerResult) {
        $wikitext = "";
        $wikitext .= $this->importMolecules($chemScannerResult);
        $wikitext .= $this->importReactions($chemScannerResult);
        $oldText = WikiTools::getText($this->getTitle());
        WikiTools::doEditContent($this->getTitle(), "$wikitext\n\n$oldText", "auto-generated");
    }

    private function importMolecules($chemScannerResult)
    {
        $wikitext = "\n== Molecules ==";
        foreach($chemScannerResult->molecules as $m) {
            $wikitext .= "\n=== {$m->label}===";
            $wikitext .= "\nDescription: {$m->text}";
            $wikitext .= "\n<chemform>\n{$m->mdl}\n</chemform>";
            $wikitext .= "\n";
        }
        return $wikitext;
    }

    private function importReactions($chemScannerResult)
    {
        $wikitext = "";
        foreach($chemScannerResult->reactions as $reaction) {
            $wikitext .= "\n== Reaction ==";
            $wikitext .= "\nDescription: {$reaction->description}";
            if (isset($reaction->time) && $reaction->time != '') {
                $wikitext .= "\nTime: {$reaction->time}";
            }
            if (isset($reaction->temperature) && $reaction->temperature != '') {
                $wikitext .= "\nTemperature: {$reaction->temperature}";
            }
            $wikitext .= "\n=== Reactants ===";
            foreach($reaction->reactants as $r) {
                $wikitext .= "\n* Name: {$r->label}";
                $wikitext .= "\n* Description: {$r->text}";
                $wikitext .= "\n<chemform smiles=\"{$r->smiles}\"></chemform>";
                $wikitext .= "\n";
            }
            $wikitext .= "\n=== Reagents ===";
            foreach($reaction->reagents as $r) {
                $wikitext .= "\n* Name: {$r->label}";
                $wikitext .= "\n* Description: {$r->text}";
                $wikitext .= "\n<chemform smiles=\"{$r->smiles}\"></chemform>";
                $wikitext .= "\n";
            }
            $wikitext .= "\n=== Products ===";
            foreach($reaction->products as $product) {
                $wikitext .= "\n* Name: {$product->label}";
                $wikitext .= "\n* Description: {$product->text}";
                $wikitext .= "\n<chemform smiles=\"{$product->smiles}\"></chemform>";
                $wikitext .= "\n";
            }
            $wikitext .= "\n=== Steps ===";
            foreach($reaction->steps as $step) {
                $wikitext .= "\n* Step: {$step->number}";
                $wikitext .= "\n* Description: {$step->description}";
                if (isset($step->time) && $step->time != '') {
                    $wikitext .= "\nTime: {$step->time}";
                }
                if (isset($step->temperature) && $step->temperature != '') {
                    $wikitext .= "\nTemperature: {$step->temperature}";
                }
                foreach($step->reagents as $reagent) {
                    $wikitext .= "\n<chemform smiles=\"{$reagent}\"></chemform>";
                }
                $wikitext .= "\n";
            }
            $wikitext .= "\n";
        }
        return $wikitext;
    }
}
