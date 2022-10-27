<?php

namespace DIQA\ChemExtension\PubChem;

use DIQA\ChemExtension\Utils\ChemTools;

class PubChemSynonymsResult extends PubChemAbstractResult
{

    /**
     * PubChemSynonymsResult constructor.
     * @param $result
     */
    public function __construct($result)
    {
        parent::__construct($result);
    }

    public function getCAS()
    {
        $synonyms = $this->getSynonyms();
        foreach($synonyms as $synonym) {
            if (ChemTools::isCASNumber($synonym)) {
                return $synonym;
            }
        }
        return '';
    }

    public function getSynonyms()
    {
        $info = $this->result->InformationList->Information[0] ?? null;
        if (is_null($info)) return [];
        $synonyms = $info->Synonym ?? [];
        return array_filter($synonyms, function($e) { return !ChemTools::isCASNumber($e); });
    }
}