<?php

namespace DIQA\FacetedSearch2\Model\Response;

class PropertyFacetValues
{
    public PropertyWithURL $property;
    public $values;

    /**
     * PropertyFacetValues constructor.
     * @param PropertyWithURL $property
     * @param $values
     */
    public function __construct(PropertyWithURL $property, $values)
    {
        $this->property = $property;
        $this->values = $values;
    }


}