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
        return count($this->getRGroups()) > 0;
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
        return is_null($this->rGroups) ? [] : $this->rGroups;
    }

    public static function fromMolOrRxn($molOrRxn, $smiles, $inchi, $inchikey, $rGroups = null): ChemForm
    {
        return new ChemForm($molOrRxn, $smiles, $inchi, $inchikey, 200, 200, 'none', $rGroups);


    }

    public function __toString()
    {
        return print_r($this, true);
    }

    public function merge(ChemForm $chemForm) {
        foreach ($this as $key => $value) {
            if (is_null($this->{$key}) || $this->{$key} === '') {
                $this->{$key} = $chemForm->{$key};
            }
        }
    }

    public function serializeAsWikitext() {
        $atts = $this->serializeAttribute('smiles');
        $atts .= $this->serializeAttribute('inchiKey');
        $atts .= $this->serializeAttribute('inchi');
        $atts .= $this->serializeAttribute('float');
        $atts .= $this->serializeAttribute('width');
        $atts .= $this->serializeAttribute('height');

        for($i = 1; $i < ChemFormParser::MAX_RGROUPS; $i++) {
            if (isset($this->{"r$i"})) {
                $atts .= $this->serializeAttribute("r$i");
            }
        }
        $atts = trim($atts);
        return "<chemform $atts>{$this->molOrRxn}</chemform>";
    }

    private function serializeAttribute($name) {
        $value = str_replace('"', '&quot;', $this->{$name});
        return sprintf(strtolower($name).'="%s" ', $value);
    }

}
