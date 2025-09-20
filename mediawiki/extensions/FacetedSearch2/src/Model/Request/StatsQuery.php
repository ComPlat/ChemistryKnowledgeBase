<?php

namespace DIQA\FacetedSearch2\Model\Request;

use DIQA\FacetedSearch2\Model\Common\Property;

class StatsQuery extends BaseQuery {

    /**
     * @var \DIQA\FacetedSearch2\Model\Common\Property[]
     */
    public $statsProperties = [];

    public static function fromJson($json): StatsQuery
    {
        $mapper = new \JsonMapper();
        return $mapper->map(json_decode($json), new StatsQuery());
    }

    /**
     * @return \DIQA\FacetedSearch2\Model\Common\Property[]
     */
    public function getStatsProperties(): array
    {
        return $this->statsProperties;
    }

    /**
     * @param \DIQA\FacetedSearch2\Model\Common\Property[] $statsProperties
     * @return StatsQuery
     */
    public function setStatsProperties(array $statsProperties): StatsQuery
    {
        $this->statsProperties = $statsProperties;
        return $this;
    }


}