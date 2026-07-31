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


    public function makeClusters(string $min, string $max, int $numSteps): array
    {
        $minNum = (float)$min;
        $maxNum = (float)$max;
        if ($min === $max) {
            return [new Range($minNum, $maxNum)];
        }
        if ($this->isInteger) {
            return $this->makeClustersInteger($minNum, $maxNum, $numSteps);
        } else {
            return $this->makeClustersFloat($maxNum, $minNum, $numSteps);
        }
    }

    private function makeClustersInteger(int $min, int $max, int $numSteps): array
    {
        $values = $this->generateClusters($max, $min, $numSteps);
        $ranges = [];
        for ($i = 0; $i < count($values) - 1; ++$i) {
            $from = round($values[$i], 0, PHP_ROUND_HALF_UP);
            $to = round($values[$i + 1], 0, PHP_ROUND_HALF_DOWN);
            $ranges[$i] = new Range($from, $to);
        }

        return $ranges;
    }

    private function makeClustersFloat(float $maxNum, float $minNum, int $numSteps): array
    {
        $values = $this->generateClusters($maxNum, $minNum, $numSteps);
        $ranges = [];
        for ($i = 0; $i < count($values) - 1; ++$i) {
            $from = round($values[$i], 2, PHP_ROUND_HALF_UP);
            $to = round($values[$i + 1], 2, PHP_ROUND_HALF_DOWN);
            $ranges[$i] = new Range($from, $to);
        }

        return $ranges;
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

    private function generateClusters(float $maxNum, float $minNum, int $numSteps): array
    {
        $diff = $maxNum - $minNum;
        $values = [];
        $currVal = $minNum;
        $incr = $diff / $numSteps;

        for ($i = 0; $i < $numSteps; ++$i) {
            $values[$i] = $currVal;
            $currVal += $incr;
        }
        $values[$i] = $maxNum;
        return $values;
    }


}