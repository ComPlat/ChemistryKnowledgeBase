<?php

namespace DIQA\ChemExtension\Pages;

class ChemForm {

    private $id;
    private $molOrRxn;
    private $isReaction;
    private $smiles;
    private $inchi;
    private $inchiKey;
    private $width;
    private $height;
    private $float;

    /**
     * @param $id
     * @param $molOrRxn
     * @param $smiles
     * @param $width
     * @param $height
     * @param $float
     */
    public function __construct($id, $molOrRxn, $reaction, $smiles, $inchi, $inchiKey, $width, $height, $float)
    {
        $this->id = $id;
        $this->molOrRxn = $molOrRxn;
        $this->isReaction = $reaction;
        $this->smiles = $smiles;
        $this->inchi = $inchi;
        $this->inchiKey = $inchiKey;
        $this->width = $width;
        $this->height = $height;
        $this->float = $float;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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

    public function __toString()
    {
        return "{$this->id} ({$this->smiles}, {$this->width}, {$this->height}, {$this->float}): {$this->molOrRxn}";
    }


}
