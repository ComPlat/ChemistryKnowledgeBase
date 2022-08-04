<?php

namespace DIQA\ChemExtension\Pages;

class ChemForm {

    private $databaseId;
    private $chemFormId;
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
    public function __construct($chemFormId, $molOrRxn, $reaction, $smiles, $inchi, $inchiKey, $width, $height, $float,
                                $rests)
    {
        $this->chemFormId = $chemFormId;
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
    public function getChemFormId()
    {
        return $this->chemFormId;
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
     * @return mixed
     */
    public function getRests()
    {
        return $this->rests;
    }



    public function __toString()
    {
        return "{$this->databaseId} ({$this->chemFormId} {$this->smiles}, {$this->width}, {$this->height},"
            ." {$this->float}): {$this->molOrRxn} | {$this->rests}";
    }


}
