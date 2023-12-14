<?php

use DIQA\ChemExtension\Utils\WikiTools;

require_once __DIR__ . '/PageIterator.php';

class updateMolecules extends PageIterator
{

    public function __construct()
    {
        parent::__construct();
        $this->addOption('dryRun', 'Dont update. Just show what would happen', false, false);
    }

    protected function init()
    {
        // when indexing everything, dependent pages do not need special treatment
        global $fsUpdateOnlyCurrentArticle;
        $fsUpdateOnlyCurrentArticle = true;
    }

    protected function processPage(Title $title)
    {

        if ($title->getNamespace() !== NS_MOLECULE) {
            return;
        }

        $categories = array_keys($title->getParentCategories());
        if (in_array('Category:Molecule_collection', $categories)) {
            print "\tSkip molecule collection: " . $title->getPrefixedText() . "\n";
            return;
        }
        global $wgCEUseMoleculeRGroupsClientMock;
        $rGroupClient = $wgCEUseMoleculeRGroupsClientMock ?
            new \DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculeRGroupServiceClientMock()
            : new \DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculeRGroupServiceClientImpl();


        $text = WikiTools::getText($title);
        $te = new \DIQA\ChemExtension\Utils\TemplateEditor($text);
        if ($te->exists('Molecule')) {
            $params = $te->getTemplateParams('Molecule');
            $mass = $params['molecularFormula'] ?? '';
            $formula = $params['molecularFormula'] ?? '';
            $molOrRxn = $params['molOrRxn'] ?? '';
            if (($mass == '' || $formula == '') && $molOrRxn != '') {
                try {
                    if (!self::startsWith($molOrRxn, "\n")) {
                        $molOrRxn = "\n$molOrRxn";
                    }
                    $metadata = $rGroupClient->getMetadata($molOrRxn);
                    if ($metadata['molecularMass'] != '') {
                        $params['molecularMass'] = $metadata['molecularMass'];
                    }
                    if ($metadata['molecularFormula'] != '') {
                        $params['molecularFormula'] = \DIQA\ChemExtension\Utils\HtmlTools::formatSumFormula($metadata['molecularFormula']);
                    }
                    $te->replaceTemplateParameters('Molecule', $params);
                    if (!$this->hasOption('dryRun')) {
                        WikiTools::doEditContent($title, $te->getWikiText(), "auto-updated mass/formula");
                    }
                    print "\tSave page: " . $title->getPrefixedText() . "\n";

                } catch (\Exception $e) {
                    print "\tProblem on page: " . $title->getPrefixedText() . "\n";
                    $this->problems[] = $title;
                }
                sleep(1); // necessary because service gets easily overloaded
            }
        }
    }

}

$maintClass = "updateMolecules";
require_once RUN_MAINTENANCE_IF_MAIN;
