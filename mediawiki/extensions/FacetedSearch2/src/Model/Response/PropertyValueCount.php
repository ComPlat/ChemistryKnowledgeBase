<?php
namespace DIQA\FacetedSearch2\Model\Response;

class PropertyValueCount
{
    public PropertyWithURL $property;
    /* @var ValueCount[] */
    public array $values;

    /**
     * PropertyValueCount constructor.
     * @param PropertyWithURL $property
     * @param ValueCount[] $values
     */
    public function __construct(PropertyWithURL $property, array $values)
    {
        $this->property = $property;
        $this->values = $values;
    }

    /**
     * @return ValueCount[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

}
