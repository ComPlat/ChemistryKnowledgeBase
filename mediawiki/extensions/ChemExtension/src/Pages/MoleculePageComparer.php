<?php

namespace DIQA\ChemExtension\Pages;

use DIQA\ChemExtension\Utils\TemplateEditor;

class MoleculePageComparer
{


    private $teOld;
    private $teNew;

    /**
     * MoleculeComparer constructor.
     * @param $wikitextOld
     * @param $wikitextNew
     */
    public function __construct($wikitextOld, $wikitextNew)
    {
        $this->teOld = new TemplateEditor($wikitextOld);
        $this->teNew = new TemplateEditor($wikitextNew);
    }

    public function getUpdatedContent()
    {
        if (!$this->teOld->exists('ChemicalReaction')
            && !$this->teOld->exists('MoleculeCollection')
            && !$this->teOld->exists('Molecule')) {
            return $this->teNew->getWikiText();
        }

        $this->updateTemplate('ChemicalReaction');
        $this->updateTemplate('MoleculeCollection', ['trivialname', 'abbrev']);
        $this->updateTemplate('Molecule', ['trivialname', 'abbrev']);

        return $this->teOld->getWikiText();
    }

    private function updateTemplate($template, $fieldsToKeep = [])
    {
        $oldParams = $this->teOld->getTemplateParams($template);
        $newParams = $this->teNew->getTemplateParams($template);
        foreach ($fieldsToKeep as $f) {
            $newParams[$f] = $oldParams[$f] ?? '';
        }
        $this->teOld->replaceTemplateParameters($template, $newParams);
    }

}
