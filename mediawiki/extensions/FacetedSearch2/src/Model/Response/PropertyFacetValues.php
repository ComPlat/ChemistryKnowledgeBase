<?php

namespace DIQA\FacetedSearch2\Model\Response;

use DIQA\FacetedSearch2\Utils\WikiTools;

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
        $this->values = array_map(fn($s) => is_string($s) ? WikiTools::stripHtml($s) : $s, $values);
    }


}