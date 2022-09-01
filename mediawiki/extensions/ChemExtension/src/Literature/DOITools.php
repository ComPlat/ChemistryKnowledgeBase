<?php

namespace DIQA\ChemExtension\Literature;

class DOITools {

    /**
     * Generates a literature reference link name.
     *
     * @param $doiData
     * @return string
     */
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

    /**
     * Parses a DOI from a URL. DOIs without URL are returned as-is.
     * @param $doiOrURL
     * @return false|mixed|string|null
     */
    public static function parseDOI($doiOrURL) {
        if (is_null($doiOrURL)) {
            return null;
        }
        $urlParts = parse_url($doiOrURL);
        if (!array_key_exists('path', $urlParts)) {
            return null;
        }
        $doi = $urlParts['path'];
        return strpos($doi, '/') === 0 ? substr($doi, 1) : $doi;
    }
}
