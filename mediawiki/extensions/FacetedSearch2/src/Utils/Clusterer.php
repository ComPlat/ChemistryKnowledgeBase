<?php

namespace DIQA\FacetedSearch2\Utils;

interface Clusterer {

    public function makeClusters(float $min, float $max, int $numSteps): array;

}