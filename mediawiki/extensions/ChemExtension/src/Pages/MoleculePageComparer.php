<?php

namespace DIQA\ChemExtension\Pages;

use DIQA\ChemExtension\Utils\TemplateEditor;

class MoleculePageComparer {


    private $te1;
    private $te2;

    /**
     * MoleculeComparer constructor.
     * @param $wikitext1
     * @param $wikitext2
     */
    public function __construct($wikitext1, $wikitext2)
    {
        $this->te1 = new TemplateEditor($wikitext1);
        $this->te2 = new TemplateEditor($wikitext2);
    }


    public function isEqual(): bool
    {

        $isEqual = $this->isTemplatesEqual('PubChem')
            && $this->isTemplatesEqual('ChemicalReaction')
            && $this->isTemplatesEqual('MoleculeCollection')
            && $this->isTemplatesEqual('ChemicalFormula');

        if ($isEqual) {
            return true;
        }

        $pubChem1 = $this->te1->getTemplateParams('PubChem');
        $pubChem2 = $this->te2->getTemplateParams('PubChem');

        // allow different trivialname and abbreviation
        unset($pubChem1['trivialname']);
        unset($pubChem1['abbrev']);
        unset($pubChem2['trivialname']);
        unset($pubChem2['abbrev']);

        return $pubChem1 == $pubChem2
            && $this->isTemplatesEqual('ChemicalReaction')
            && $this->isTemplatesEqual('MoleculeCollection')
            && $this->isTemplatesEqual('ChemicalFormula');
    }

    private function isTemplatesEqual($templateName): bool
    {
        return $this->te1->getTemplateParams($templateName) == $this->te2->getTemplateParams($templateName);
    }
}
