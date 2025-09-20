<?php

namespace DIQA\FacetedSearch2\Model\Request;

use DIQA\FacetedSearch2\Model\Common\Property;

class PropertyValueQuery {

    public Property $property;

    public ?int $valueLimit = null;
    public ?int $valueOffset = null;
    public ?string $valueContains = null;

    /**
     * Property constructor.
     * @param \DIQA\FacetedSearch2\Model\Common\Property $property
     * @param int|null $facetLimit
     * @param int|null $facetOffset
     * @param string|null $facetContains
     */
    public function __construct(Property $property,
                                ?int $facetLimit = null,
                                ?int $facetOffset = null,
                                ?string $facetContains = null)
    {
        $this->property = $property;
        $this->valueLimit = $facetLimit;
        $this->valueOffset = $facetOffset;
        $this->valueContains = $facetContains;
    }

    /**
     * @return \DIQA\FacetedSearch2\Model\Common\Property
     */
    public function getProperty(): Property
    {
        return $this->property;
    }

    /**
     * @return int|null
     */
    public function getValueLimit(): ?int
    {
        return $this->valueLimit;
    }

    /**
     * @return int|null
     */
    public function getValueOffset(): ?int
    {
        return $this->valueOffset;
    }

    /**
     * @return string|null
     */
    public function getValueContains(): ?string
    {
        return $this->valueContains;
    }

    public function hasConstraints() {
        return !(is_null($this->valueLimit) && is_null($this->valueOffset) && is_null($this->valueContains));
    }

}