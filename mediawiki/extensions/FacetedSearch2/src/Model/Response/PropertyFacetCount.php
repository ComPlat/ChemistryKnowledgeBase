<?php
namespace DIQA\FacetedSearch2\Model\Response;

class PropertyFacetCount
{
    public PropertyWithURL $property;
    public int $count;

    /**
     * PropertyFacetCount constructor.
     * @param PropertyWithURL $property
     * @param int $count
     */
    public function __construct(PropertyWithURL $property, int $count)
    {
        $this->property = $property;
        $this->count = $count;
    }


}
