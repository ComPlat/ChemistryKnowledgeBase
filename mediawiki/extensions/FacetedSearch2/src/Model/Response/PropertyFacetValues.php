<?php

namespace DIQA\FacetedSearch2\Model\Response;

class PropertyFacetValues
{
    public PropertyWithURL $property;
    public array $values;

    /**
     * PropertyFacetValues constructor.
     * @param PropertyWithURL $property
     * @param array $values
     */
    public function __construct(PropertyWithURL $property, array $values)
    {
        $this->property = $property;
        $this->values = $values;
    }


}