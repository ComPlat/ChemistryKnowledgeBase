<?php

namespace DIQA\ChemExtension\Literature;

use DateTime;
use DIQA\ChemExtension\Utils\ArrayTools;
use Title;
use SMWDIWikiPage;
use SMWDIProperty;
use SMWDIBlob;

class DOITools {

    /**
     * Generates a literature reference link name.
     *
     * @param $doiData
     * @return string
     */
    public static function generateReferenceIndex($doiData): string
    {
        if ($doiData === '__placeholder__') {
            return '__';
        }
        $year = $doiData->issued->{"date-parts"}[0][0] ?? '';
        if ($year === '') {
            $year = $doiData->{'published-print'}->{'date-parts'}[0] ?? '';
        }
        if ($year === '') {
            $year = $doiData->{'published-online'}->{'date-parts'}[0] ?? '';
        }

        preg_match_all('/([A-Za-z])[A-Za-z]+/', strip_tags(ArrayTools::getFirstIfArray($doiData->title)), $matches);
        $capitals = implode('', $matches[1]);
        $titleAbbrev = substr($capitals, 0, 3);

        if (strlen($year) > 2) {
            $year = substr($year, -2);
        }
        return $titleAbbrev . $year;
    }

    /**
     * Generates a literature reference link name.
     *
     * @param $doiData
     * @return string
     */
    public static function generateReferenceIndexFromTitle(Title $title): string
    {
        preg_match_all('/([A-zöäüÖÄÜ])\w*/', strip_tags(ArrayTools::getFirstIfArray($title->getText())), $matches);
        $capitals = implode('', $matches[1]);
        return substr($capitals, 0, 3);
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
        if (strpos($doi, 'doi.org/') === 0) {
            $doi = substr($doi, strlen('doi.org/'));
        }
        return strpos($doi, '/') === 0 ? substr($doi, 1) : $doi;
    }

    public static function formatLicenses($licenses): array
    {
        if ($licenses == '') {
            return [];
        }
        $result = [];
        foreach ($licenses as $license) {
            $date = self::parseDateFromDateParts($license->start->{'date-parts'}) ?? '-';
            $result[] = ['date' => $date, 'URL' => $license->URL];
        }
        return $result;
    }

    public static function parseDateFromDateParts($dateParts)
    {
        if ($dateParts == '') {
            return null;
        }
        $first = $dateParts[0]; // why several at all??
        if (!is_array($first)) {
            return '';
        }
        if (count($first) === 1) {
            return $first[0]; // year
        } else if (count($first) === 2) {
            $year = $first[0];
            $dateObj = DateTime::createFromFormat('!m', $first[1]);
            $monthName = $dateObj->format('F');
            return "$monthName $year";
        } else {
            return date('d.m.Y', strtotime("{$first[0]}/{$first[1]}/{$first[2]}"));
        }
    }

    public static function formatAuthors($authors): array
    {
        if ($authors == '') {
            return [];
        }
        $result = [];
        foreach ($authors as $author) {
            $affiliation = implode(", ", array_map(function ($e) {
                return $e->name;
            }, $author->affiliation));
            $name = "{$author->given} {$author->family}";
            $result[] = ['name' => $name, 'nameAndAfiliation' => "$name, $affiliation", 'afiliation' => $affiliation, 'orcidUrl' => $author->ORCID ?? ''];
        }
        return $result;
    }

    public static function getTypeLabel($typeId): string
    {
        switch ($typeId) {

            case "book-section":
                return "Book Section";
            case "monograph":
                return "Monograph";
            case "report":
                return "Report";
            case "peer-review":
                return "Peer Review";
            case "book-track":
                return "Book Track";
            case "journal-article":
                return "Journal Article";
            case "book-part":
                return "Part";
            case "other":
                return "Other";
            case "book":
                return "Book";
            case "journal-volume":
                return "Journal Volume";
            case "book-set":
                return "Book Set";
            case "reference-entry":
                return "Reference Entry";
            case "proceedings-article":
                return "Proceedings Article";
            case "journal":
                return "Journal";
            case "component":
                return "Component";
            case "book-chapter":
                return "Book Chapter";
            case "proceedings-series":
                return "Proceedings Series";
            case "report-series":
                return "Report Series";
            case "proceedings":
                return "Proceedings";
            case "standard":
                return "Standard";
            case "reference-book":
                return "Reference Book";
            case "posted-content":
                return "Posted Content";
            case "journal-issue":
                return "Journal Issue";
            case "dissertation":
                return "Dissertation";
            case "grant":
                return "Grant";
            case "dataset":
                return "Dataset";
            case "book-series":
                return "Book Series";
            case "edited-book":
                return "Edited Book";
            case "standard-series":
                return "Standard Series";
            default: {
                return is_null($typeId) || $typeId == '' ? '-' : $typeId;
            }
        }
    }

    public static function getDOIFromPage($pageTitle) {
        $doiResult = smwfGetStore()->getPropertyValues(SMWDIWikiPage::newFromTitle($pageTitle),
            SMWDIProperty::newFromUserLabel("DOI"));
        if (count($doiResult) > 0) {
            $first = reset($doiResult);
            return $first->getString();
        }
        return null;
    }

    public static function getPageFromDOI($doi) {
        $doiResult = smwfGetStore()->getPropertySubjects(SMWDIProperty::newFromUserLabel("DOI"), new SMWDIBlob($doi));
        $doiResult->rewind();
        return $doiResult->valid() ? $doiResult->current()->getTitle() : null;
    }

}
