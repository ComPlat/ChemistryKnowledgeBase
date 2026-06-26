<?php

namespace DIQA\FacetedSearch2\Model\Response;

use DIQA\FacetedSearch2\Model\Common\Datatype;
use DIQA\FacetedSearch2\Utils\DateTimeClusterer;
use DIQA\FacetedSearch2\Utils\NumericClusterer;

class Stats
{
    public PropertyWithURL $property;
    public ?string $min;
    public ?string $max;
    public ?int $count;
    public ?string $sum;
    public array $clusters;

    /**
     * Stats constructor.
     * @param PropertyWithURL $property
     * @param string|null $min
     * @param string|null $max
     * @param int|null $count
     * @param string|null $sum
     */
    public function __construct(PropertyWithURL $property, ?string $min, ?string $max, ?int $count, ?string $sum)
    {
        $this->property = $property;
        $this->min = $min;
        $this->max = $max;
        $this->count = $count;
        $this->sum = $sum;
        $this->makeClusters($property, $min, $max, $sum);
    }

    /**
     * @return PropertyWithURL
     */
    public function getProperty(): PropertyWithURL
    {
        return $this->property;
    }

    /**
     * @return string|null
     */
    public function getMin(): ?string
    {
        return $this->min;
    }

    /**
     * @return string|null
     */
    public function getMax(): ?string
    {
        return $this->max;
    }

    /**
     * @return int|null
     */
    public function getCount(): ?int
    {
        return $this->count;
    }

    /**
     * @return string|null
     */
    public function getSum(): ?string
    {
        return $this->sum;
    }

    private function makeClusters(PropertyWithURL $property, ?string $min, ?string $max, ?string $sum): void
    {
        switch($property->getType()) {
            case Datatype::DATETIME:
                $clusterer = new DateTimeClusterer();
                global $fs2gDateTimePropertyClusters;
                if (array_key_exists($property->getTitle(), $fs2gDateTimePropertyClusters)) {
                    $constraints = $fs2gDateTimePropertyClusters[$property->getTitle()];
                    $min = $constraints['min'];
                    $max = $constraints['max'];
                }
                $this->clusters = $clusterer->makeClusters($min, $max, 10);
                break;
            case Datatype::NUMBER:
                $isInteger = ctype_digit((string) abs($sum));
                $clusterer = new NumericClusterer($isInteger);
                global $fs2gNumericPropertyClusters;
                if (array_key_exists($property->getTitle(), $fs2gNumericPropertyClusters)) {
                    $constraints = $fs2gNumericPropertyClusters[$property->getTitle()];
                    $this->clusters = $clusterer->makeClustersWithFixedInterval(
                        $constraints['lowerBound'],
                        $constraints['upperBound'],
                        $constraints['interval'],
                        $constraints['min'] ?? null,
                        $constraints['max'] ?? null);
                } else {
                    $this->clusters = $clusterer->makeClusters($min, $max, 10);
                }
                break;
            default:
                $this->clusters = [];
        }
    }


}
