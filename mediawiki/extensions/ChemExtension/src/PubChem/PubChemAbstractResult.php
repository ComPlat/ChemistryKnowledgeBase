<?php

namespace DIQA\ChemExtension\PubChem;

abstract class PubChemAbstractResult {

    protected $result;

    /**
     * PubChemAbstractResult constructor.
     * @param $result
     */
    public function __construct($result)
    {
        $this->result = $result;
    }

    /**
     * @return mixed
     */
    public function getRawResult()
    {
        return $this->result;
    }


}