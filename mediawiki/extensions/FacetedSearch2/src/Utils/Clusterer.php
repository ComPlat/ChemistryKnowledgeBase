<?php

namespace DIQA\FacetedSearch2\Utils;

interface Clusterer {

    public function makeClusters(int $min, int $max, int $numSteps): array;

}