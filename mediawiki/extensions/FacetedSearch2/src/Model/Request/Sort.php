<?php

namespace DIQA\FacetedSearch2\Model\Request;

use DIQA\FacetedSearch2\Model\Common\Property;

class Sort {

    public Property $property;
    public int $order;

    /**
     * Sort constructor.
     * @param \DIQA\FacetedSearch2\Model\Common\Property $property
     * @param int $order
     */
    public function __construct(Property $property, int $order)
    {
        $this->property = $property;
        $this->order = $order;
    }

    /**
     * @return \DIQA\FacetedSearch2\Model\Common\Property
     */
    public function getProperty(): Property
    {
        return $this->property;
    }

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order;
    }


}