<?php

namespace DIQA\FacetedSearch2\Utils;


use DIQA\FacetedSearch2\Model\Common\Range;

class NumericClusterer implements Clusterer
{

    private bool $isInteger;

    public function __construct($isInteger)
    {
        $this->isInteger = $isInteger;
    }


    public function makeClusters(float $min, float $max, int $numSteps): array
    {
        if ($min === $max) {
            return [new Range($min, $max)];
        }
        if ($this->isInteger) {
            return $this->makeClustersInteger($min, $max, $numSteps);
        }
        $diff =  $max - $min;
        $values = [];
        $currVal = $min;
        $incr =  $diff / $numSteps;

        for ($i = 0; $i < $numSteps; ++$i) {
            $values[$i] = round($currVal, 2);
            $currVal += $incr;
        }
        $values[$i] = $max;
        for ($i = 0; $i < count($values) - 1; ++$i) {
            $values[$i] = new Range($values[$i], $values[$i + 1]);
        }
        array_splice($values, count($values) - 1, 1);

        return $values;
    }

    public function makeClustersInteger(int $min, int $max, int $numSteps): array
    {
        $diff = $max - $min;
        $values = [];
        $currVal = $min;
        $incr = $diff / $numSteps;

        for ($i = 0; $i < $numSteps; ++$i) {
            $values[$i] = round($currVal);
            $currVal += $incr;
        }
        $values[$i] = $max + 1;
        for ($i = 0; $i < count($values) - 1; ++$i) {
            $values[$i] = new Range($values[$i], $values[$i + 1] - 1);
        }
        array_splice($values, count($values) - 1, 1);

        return $values;
    }

    public function makeClustersWithFixedInterval(int $lowerBound, int $upperBound, int $interval,
                                                  int $min = null, int $max = null): array
    {
        $ranges = [];
        if (!is_null($min)) {
            $ranges[] = new Range($min, $lowerBound - 1);
        }
        for($i = $lowerBound; $i <= $upperBound - $interval; $i += $interval) {
            $ranges[] = new Range($i, $i + $interval - 1);
        }
        if (!is_null($max)) {
            $ranges[] = new Range($upperBound, $max);
        }
        return $ranges;
    }
}