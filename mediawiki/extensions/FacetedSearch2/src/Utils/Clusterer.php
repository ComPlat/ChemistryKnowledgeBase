<?php

namespace DIQA\FacetedSearch2\Utils;

interface Clusterer {

    public function makeClusters(string $min, string $max, int $numSteps): array;

}