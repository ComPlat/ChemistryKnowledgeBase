<?php

namespace DIQA\ChemExtension\Pages;

use DIQA\ChemExtension\Utils\MolfileProcessor;

class ChemForm
{

    private $molOrRxn;
    private $smiles;
    private $inchi;
    private $inchiKey;
    private $width;
    private $height;
    private $float;
    private $rGroups;

    public function __construct($molOrRxn, $smiles, $inchi, $inchiKey, $width, $height, $float,
                                $rGroups)
    {
        $this->molOrRxn = $molOrRxn;
        $this->smiles = $smiles;
        $this->inchi = $inchi;
        $this->inchiKey = $inchiKey;
        $this->width = $width;
        $this->height = $height;
        $this->float = $float;
        $this->rGroups = $rGroups;
    }

    /**
     * @return mixed
     */
    public function getMoleculeKey()
    {
        return MolfileProcessor::generateMoleculeKey($this->molOrRxn, $this->smiles, $this->inchiKey);
    }


    /**
     * @return mixed
     */
    public function getMolOrRxn()
    {
        return $this->molOrRxn;
    }

    /**
     * @return mixed
     */
    public function isReaction()
    {
        return MolfileProcessor::isReactionFormula($this->molOrRxn);
    }



    public function hasRGroupDefinitions()
    {
        return !is_null($this->getRGroups()) && count($this->getRGroups()) > 0;
    }

    /**
     * @return mixed
     */
    public function getSmiles()
    {
        return $this->smiles;
    }

    /**
     * @return mixed
     */
    public function getInchi()
    {
        return $this->inchi;
    }

    /**
     * @return mixed
     */
    public function getInchiKey()
    {
        return $this->inchiKey;
    }

    /**
     * @return mixed
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return mixed
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return mixed
     */
    public function getFloat()
    {
        return $this->float;
    }

    /**
     * Hash array mapping R-Groups to array of molecules
     * [
     *  'R1' => [ 'CH2', 'OH', ... ],
     *  'R2' => [ 'OH', 'SO4', ... ],
     *   ...
     * ]
     * @return mixed
     */
    public function getRGroups()
    {
        return $this->rGroups;
    }

    public static function fromMolOrRxn($molOrRxn, $smiles, $inchi, $inchikey): ChemForm
    {
        return new ChemForm($molOrRxn, $smiles, $inchi, $inchikey, 200, 200, 'none', null);


    }

    public function __toString()
    {
        return print_r($this, true);
    }


}
