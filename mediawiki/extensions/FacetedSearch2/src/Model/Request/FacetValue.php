<?php
namespace DIQA\FacetedSearch2\Model\Request;

use DIQA\FacetedSearch2\Model\Common\MWTitle;
use DIQA\FacetedSearch2\Model\Common\Range;

class FacetValue {

    public ?string $value = null;
    public ?MWTitle $mwTitle = null;
    public ?Range $range = null;

    /**
     * FacetValue constructor.
     * @param string|null $value
     * @param MWTitle|null $mwTitle
     * @param Range|null $range
     */
    public function __construct(?string $value = null, ?MWTitle $mwTitle = null, ?Range $range = null)
    {
        $this->value = $value;
        $this->mwTitle = $mwTitle;
        $this->range = $range;
    }

    public static function allValues() {
        return new FacetValue();
    }

    public static function fromValue($value) {
        return new FacetValue($value);
    }

    public static function fromTitle(MWTitle $MWTitle) {
        return new FacetValue(null, $MWTitle);
    }

    public static function fromRange(Range $range) {
        return new FacetValue(null, null, $range);
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string|null $value
     * @return FacetValue
     */
    public function setValue(?string $value): FacetValue
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return MWTitle|null
     */
    public function getMwTitle(): ?MWTitle
    {
        return $this->mwTitle;
    }

    /**
     * @param MWTitle|null $mwTitle
     * @return FacetValue
     */
    public function setMwTitle(?MWTitle $mwTitle): FacetValue
    {
        $this->mwTitle = $mwTitle;
        return $this;
    }

    /**
     * @return Range|null
     */
    public function getRange(): ?Range
    {
        return $this->range;
    }

    /**
     * @param Range|null $range
     * @return FacetValue
     */
    public function setRange(?Range $range): FacetValue
    {
        $this->range = $range;
        return $this;
    }


}
