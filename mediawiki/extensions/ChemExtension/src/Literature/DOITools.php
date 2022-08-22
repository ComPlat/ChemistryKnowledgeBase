<?php

namespace DIQA\ChemExtension\Literature;

class DOITools {

    public static function generateReferenceIndex($doiData): string
    {
        $year = $doiData->issued->{"date-parts"}[0][0] ?? '';
        if ($year === '') {
            $year = $doiData->{'published-print'}->{'date-parts'}[0] ?? '';
        }
        if ($year === '') {
            $year = $doiData->{'published-online'}->{'date-parts'}[0] ?? '';
        }

        preg_match_all('/([A-zöäüÖÄÜ])\w*/', strip_tags($doiData->title), $matches);
        $capitals = implode('', $matches[1]);
        $titleAbbrev = substr($capitals, 0, 3);

        if (strlen($year) > 2) {
            $year = substr($year, -2);
        }
        return $titleAbbrev . $year;
    }
}
