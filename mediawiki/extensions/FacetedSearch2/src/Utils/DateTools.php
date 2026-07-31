<?php

namespace DIQA\FacetedSearch2\Utils;

use Carbon\Carbon;

class DateTools
{
    public static function isBeginOfDay(Carbon $d): bool
    {
        return $d->hour === 0
            && $d->minute === 0
            && $d->second === 0;
    }

    public static function isEndOfDay(Carbon $d): bool
    {
        return $d->hour === 23
            && $d->minute === 59
            && $d->second === 59;
    }

    public static function isBeginOfMonth(Carbon $d): bool
    {
        return $d->day === 1 && self::isBeginOfDay($d);
    }

    public static function isEndOfMonth(Carbon $d): bool
    {
        $datePlusOneSecond = $d->copy()->addSecond();
        return self::isBeginOfMonth($datePlusOneSecond);
    }

    public static function isBeginOfYear(Carbon $d): bool
    {
        return $d->month === 1 && self::isBeginOfMonth($d);
    }

    public static function isEndOfYear(Carbon $d): bool
    {
        return $d->month === 12 && self::isEndOfMonth($d);
    }
}