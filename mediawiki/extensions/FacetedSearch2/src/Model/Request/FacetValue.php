<?php
namespace DIQA\FacetedSearch2\Model\Request;

use DIQA\FacetedSearch2\Model\Common\Datatype;
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

    public static function allValues(): FacetValue
    {
        return new FacetValue();
    }

    public static function fromValue($value): FacetValue
    {
        return new FacetValue($value);
    }

    public static function fromTitle(MWTitle $MWTitle): FacetValue
    {
        return new FacetValue(null, $MWTitle);
    }

    public static function fromRange(Range $range): FacetValue
    {
        return new FacetValue(null, null, $range);
    }

    public function isAllValues(): bool
    {
        return is_null($this->value) && is_null($this->mwTitle) && is_null($this->range);
    }

    /**
     * @param int $type
     * @return string|null
     */
    public function getValue(int $type): ?string
    {
        if (is_null($this->value)) {
            return null;
        }
        return match ($type) {
            Datatype::BOOLEAN => $this->value ? 'true' : 'false',
            default => $this->value,
        };
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
