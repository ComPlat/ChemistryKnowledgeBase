<?php
namespace DIQA\FacetedSearch2\Model\Response;

use DIQA\FacetedSearch2\Model\Common\Range;
use DIQA\FacetedSearch2\Utils\WikiTools;

class ValueCount
{
    public ?string $value;
    public ?MWTitleWithURL $mwTitle;
    public ?Range $range;
    public int $count;

    public function __construct(?string $value, ?MWTitleWithURL $mwTitle, ?Range $range, int $count)
    {
        $this->value = !is_null($value) ? WikiTools::stripHtml($value) : null;
        $this->mwTitle = $mwTitle;
        $this->range = $range;
        $this->count = $count;
    }

    public static function fromValue($value, $count): self
    {
        return new ValueCount($value, null, null, $count);
    }

    public static function fromTitle(MWTitleWithURL $mwTitle, $count): self
    {
        return new ValueCount(null, $mwTitle, null, $count);
    }

    public static function fromRange(Range $range, $count): self
    {
        return new ValueCount(null, null, $range, $count);
    }
}
