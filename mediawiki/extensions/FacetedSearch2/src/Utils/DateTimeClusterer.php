<?php

namespace DIQA\FacetedSearch2\Utils;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use DIQA\FacetedSearch2\Model\Common\Range;

class DateTimeClusterer implements Clusterer
{

    private Carbon $mStart;
    private Carbon $mEnd;
    private Carbon $mCurrent;

    private $mIncrement;
    private int $numSteps;
    private int $currentStep;

    private const DATETIME_FORMAT = 'Y-m-d\TH:i:s\Z';

    public function makeClusters(int $min, int $max, int $numSteps): array
    {
        $this->numSteps = $numSteps;
        $this->currentStep = 0;

        $this->findIncrement($min, $max, $this->numSteps);
        $values = [];

        $lowerVal = $this->next();
        while (($upperVal = $this->next()) !== null) {
            $temp = clone $upperVal;
            if ($upperVal->second != 59 && !$upperVal->equalTo($lowerVal)) {
                $upperVal->subSecond();
            }
            $values[] = new Range(
                $lowerVal->format(self::DATETIME_FORMAT),
                $upperVal->format(self::DATETIME_FORMAT)
            );
            $lowerVal = $temp;
        }
        return $values;
    }

    public function next()
    {
        if ($this->currentStep > $this->numSteps) {
            return null;
        }
        if ($this->currentStep == $this->numSteps) {
            $this->currentStep++;
            return $this->mEnd;
        }

        $current = clone $this->mCurrent;
        $this->currentStep++;
        $this->mCurrent = $this->mCurrent->add($this->mIncrement);
        return $current;
    }

    private function findIncrement(int $min, int $max, int $numSteps)
    {
        if ($min === 0) {
            $min = "00000101000000"; // 1. Jan 0000, 0:00:00am
        }
        if ($max === 0) {
            $max = "99991231235959"; // 31. Dec 9999 23:59:59pm
        }
        $minDT = Carbon::createFromIsoFormat('YYYYMMDDHHmmss', $min);
        $maxDT = Carbon::createFromIsoFormat('YYYYMMDDHHmmss', $max);
        $start = [
            "year" => $minDT->year,
            "month" => 1,
            "day" => 1,
            "hour" => 0,
            "min" => 0,
            "sec" => 0
        ];
        $end = [
            "year" => $maxDT->year,
            "month" => 12,
            "day" => 31,
            "hour" => 23,
            "min" => 59,
            "sec" => 59
        ];

        $mIncrementField = false;

        $diff = $maxDT->diffInYears($minDT);
        if ($diff >= $numSteps) {
            $mIncrementField = "year";
        }
        if ($mIncrementField === false) {
            $diff = $maxDT->diffInMonths($minDT);
            $start["month"] = $minDT->month;
            $end["month"] = $maxDT->month;
            if ($diff >= $numSteps) {
                $mIncrementField = "month";
            }
        }
        if ($mIncrementField === false) {
            $diff = $maxDT->diffInDays($minDT);
            $start["day"] = $minDT->day;
            $end["day"] = $maxDT->day;
            if ($diff >= $numSteps) {
                $mIncrementField = "day";
            }
        }
        if ($mIncrementField === false) {
            $diff = $maxDT->diffInHours($minDT);
            $start["hour"] = $minDT->hour;
            $end["hour"] = $maxDT->hour;
            if ($diff >= $numSteps) {
                $mIncrementField = "hour";
            }
        }
        if ($mIncrementField === false) {
            $diff = $maxDT->diffInMinutes($minDT);
            $start["min"] = $minDT->minute;
            $end["min"] = $maxDT->minute;
            if ($diff >= $numSteps) {
                $mIncrementField = "min";
            }
        }
        if ($mIncrementField === false) {
            $diff = $maxDT->diffInSeconds($minDT);
            $start["sec"] = $minDT->second;
            $end["sec"] = $maxDT->second;
            if ($diff >= $numSteps) {
                $mIncrementField = "sec";
            }
        }
        $this->mStart = Carbon::create($start['year'], $start['month'], $start['day'], $start['hour'], $start['min'], $start['sec']);
        $this->mEnd = Carbon::create($end['year'], $end['month'], $end['day'], $end['hour'], $end['min'], $end['sec']);
        $this->mCurrent = $this->mStart;
        CarbonInterval::enableFloatSetters();
        switch ($mIncrementField) {
            case 'year':
                $diff = $this->mEnd->diffInYears($this->mStart);
                $this->mIncrement = CarbonInterval::years($diff / $numSteps);
                break;
            case 'month':
                $diff = $this->mEnd->diffInMonths($this->mStart);
                $this->mIncrement = CarbonInterval::months($diff / $numSteps);
                break;
            case 'day':
                $diff = $this->mEnd->diffInDays($this->mStart);
                $this->mIncrement = CarbonInterval::days($diff / $numSteps);
                break;
            case 'hour':
                $diff = $this->mEnd->diffInHours($this->mStart);
                $this->mIncrement = CarbonInterval::hours($diff / $numSteps);
                break;
            case 'min':
                $diff = $this->mEnd->diffInMinutes($this->mStart);
                $this->mIncrement = CarbonInterval::minutes($diff / $numSteps);
                break;
            case 'sec':
            default:
                $diff = $this->mEnd->diffInSeconds($this->mStart);
                $this->mIncrement = CarbonInterval::seconds($diff / $numSteps);
                break;
        }

    }
}