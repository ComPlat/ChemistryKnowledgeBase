<?php

namespace DIQA\ChemExtension\PubChem;

class PubChemRecordResult extends PubChemAbstractResult {


    /**
     * PubChemResult constructor.
     * @param $result
     */
    public function __construct($result)
    {
        parent::__construct($result);
    }

    public function getCID() {
        return $this->result->PC_Compounds[0]->id->id->cid ?? null;
    }

    private function getURNProp($name, $label) {
        $props = $this->result->PC_Compounds[0]->props;
        foreach($props as $p) {
            $urnName = $p->urn->name ?? null;
            $urnLabel = $p->urn->label ?? null;

            if (($name == $urnName || is_null($name))
                && ($label == $urnLabel || is_null($label))) {
                return $p->value->sval ?? $p->value->fval;
            }
        }
        return '';
    }

    public function getSMILES() {
        return $this->getURNProp('Canonical', 'SMILES');
    }

    public function getIUPACName() {
        return $this->getURNProp('Systematic', 'IUPAC Name');
    }

    public function getMolecularMass() {
        return $this->getURNProp('Exact', 'Mass');
    }

    public function getMolecularFormula() {
        return $this->getURNProp(null, 'Molecular Formula');
    }

    public function getLogP() {
        return $this->getURNProp('XLogP3', 'Log P');
    }

}