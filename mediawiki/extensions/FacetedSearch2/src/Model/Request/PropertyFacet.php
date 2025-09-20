<?php

namespace DIQA\FacetedSearch2\Model\Request;

use DIQA\FacetedSearch2\Model\Common\Property;

class PropertyFacet {

    public Property $property;

    /** @var FacetValue[] */
    public array $values;

    /**
     * PropertyFacet constructor.
     * @param \DIQA\FacetedSearch2\Model\Common\Property $property
     * @param array $values
     */
    public function __construct(Property $property, array $values = [])
    {
        $this->property = $property;
        $this->values = $values;
    }

    /**
     * @return \DIQA\FacetedSearch2\Model\Common\Property
     */
    public function getProperty(): Property
    {
        return $this->property;
    }

    /**
     * @param \DIQA\FacetedSearch2\Model\Common\Property $property
     * @return PropertyFacet
     */
    public function setProperty(Property $property): PropertyFacet
    {
        $this->property = $property;
        return $this;
    }

    /**
     * @return FacetValue[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param FacetValue[] $values
     * @return PropertyFacet
     */
    public function setValues(array $values): PropertyFacet
    {
        $this->values = $values;
        return $this;
    }

}