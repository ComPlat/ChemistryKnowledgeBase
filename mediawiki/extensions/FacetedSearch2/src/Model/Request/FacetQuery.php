<?php

namespace DIQA\FacetedSearch2\Model\Request;


class FacetQuery extends BaseQuery {

    /**
     * @var \DIQA\FacetedSearch2\Model\Common\Property[]
     */
    public $rangeQueries = [];

    /**
     * @var PropertyValueQuery[]
     */
    public $propertyValueQueries = [];

    public static function fromJson($json): FacetQuery
    {
        $mapper = new \JsonMapper();
        return $mapper->map(json_decode($json), new FacetQuery());
    }

    /**
     * @return \DIQA\FacetedSearch2\Model\Common\Property[]
     */
    public function getRangeQueries(): array
    {
        return $this->rangeQueries;
    }

    /**
     * @param \DIQA\FacetedSearch2\Model\Common\Property[] $rangeQueries
     * @return FacetQuery
     */
    public function setRangeQueries(array $rangeQueries): FacetQuery
    {
        $this->rangeQueries = $rangeQueries;
        return $this;
    }

    /**
     * @return PropertyValueQuery[]
     */
    public function getPropertyValueQueries(): array
    {
        return $this->propertyValueQueries;
    }

    /**
     * @param PropertyValueQuery[] $propertyValueQueries
     * @return FacetQuery
     */
    public function setPropertyValueQueries(array $propertyValueQueries): FacetQuery
    {
        $this->propertyValueQueries = $propertyValueQueries;
        return $this;
    }


}