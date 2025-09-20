<?php

namespace DIQA\FacetedSearch2\Model\Update;

use DIQA\FacetedSearch2\Model\Common\Property;
use DIQA\FacetedSearch2\Model\Common\Datatype;
use DIQA\FacetedSearch2\Model\Common\MWTitle;


class PropertyValues {

    private Property $property;
    private $values = []            /* @var string[] */;
    private $mwTitles = []          /* @var MWTitle[] */;

    /**
     * PropertyValues constructor.
     * @param Property $property
     * @param $values
     */
    public function __construct(Property $property, $values)
    {
        $this->property = $property;
        if ($property->getType() === Datatype::WIKIPAGE) {
            $this->mwTitles = $values ?? [];
        } else {
            $this->values = $values ?? [];
        }
    }

    /**
     * @return Property
     */
    public function getProperty(): Property
    {
        return $this->property;
    }

    /**
     * @return mixed
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @return mixed
     */
    public function getMwTitles()
    {
        return $this->mwTitles;
    }


}
