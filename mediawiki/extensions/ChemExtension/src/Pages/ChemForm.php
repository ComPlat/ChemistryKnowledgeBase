<?php

namespace DIQA\ChemExtension\Pages;

use DIQA\ChemExtension\Utils\MolfileProcessor;

class ChemForm
{

    private $databaseId;
    private $molOrRxn;
    private $isReaction;
    private $smiles;
    private $inchi;
    private $inchiKey;
    private $width;
    private $height;
    private $float;
    private $rests;

    /**
     * @param $id
     * @param $molOrRxn
     * @param $smiles
     * @param $width
     * @param $height
     * @param $float
     */
    public function __construct($molOrRxn, $reaction, $smiles, $inchi, $inchiKey, $width, $height, $float,
                                $rests)
    {
        $this->molOrRxn = $molOrRxn;
        $this->isReaction = $reaction;
        $this->smiles = $smiles;
        $this->inchi = $inchi;
        $this->inchiKey = $inchiKey;
        $this->width = $width;
        $this->height = $height;
        $this->float = $float;
        $this->rests = $rests;
    }

    /**
     * @param mixed $databaseId
     */
    public function setDatabaseId($databaseId): void
    {
        $this->databaseId = $databaseId;
    }


    /**
     * @return mixed
     */
    public function getDatabaseId()
    {
        return $this->databaseId;
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
        return $this->isReaction === '1';
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
     * Hash array mapping rests to array of molecules
     * [
     *  'R1' => [ 'CH2', 'OH', ... ],
     *  'R2' => [ 'OH', 'SO4', ... ],
     *   ...
     * ]
     * @return mixed
     */
    public function getRests()
    {
        return $this->rests;
    }

    public static function fromMolOrRxn($molOrRxn, $inchi, $inchikey)
    {
        $isReaction = strpos(trim($molOrRxn), '$RXN') === 0;
        return new ChemForm($molOrRxn, $isReaction,
            '', $inchi, $inchikey, 200, 200, 'none', null);


    }

    public function __toString()
    {
        return print_r($this, true);
    }


}
