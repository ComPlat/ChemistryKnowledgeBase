<?php

namespace DIQA\ChemExtension\PubChem;

class PubChemSynonymsResult extends PubChemAbstractResult
{
    const CAS_PATTERN = '/\d{2,7}-\d{2}-\d/';

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
            if (preg_match(self::CAS_PATTERN, $synonym, $matches) === 1) {
                return $synonym;
            }
        }
        return '';
    }

    public function getSynonyms()
    {
        $info = $this->result->InformationList->Information[0] ?? null;
        if (is_null($info)) return [];
        return $info->Synonym ?? [];
    }
}