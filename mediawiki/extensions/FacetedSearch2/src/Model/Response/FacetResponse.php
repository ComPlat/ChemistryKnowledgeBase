<?php

namespace DIQA\FacetedSearch2\Model\Response;

class FacetResponse
{
    use DebugInfo;

    /* @var PropertyValueCount[] */
    public array $valueCounts;

    public function __construct(array $valueCounts)
    {
        $this->valueCounts = $valueCounts;
    }


    /**
     * @return PropertyValueCount[]
     */
    public function getValueCounts(): array
    {
        return $this->valueCounts;
    }

    public function merge(FacetResponse $response) {
        $this->valueCounts = array_merge($this->valueCounts, $response->valueCounts);
    }
}
