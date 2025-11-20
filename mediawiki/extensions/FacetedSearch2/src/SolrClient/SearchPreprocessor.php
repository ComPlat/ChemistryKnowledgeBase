<?php

namespace DIQA\FacetedSearch2\SolrClient;

class SearchPreprocessor {

    public static function encodeSearchQuery($searchText): string
    {
        $escapedANDedTerms = self::prepareTitleQuery($searchText);
        $qs = self::prepareQueryString($searchText);

        return "smwh_search_field:(${qs}) OR "
             . "smwh_search_field:(${escapedANDedTerms}) OR "
             . "smwh_title:(${escapedANDedTerms}) OR "
             . "smwh_displaytitle:(${escapedANDedTerms})";

    }

    private static function prepareTitleQuery($searchText): string
    {
        $exactMatchQuery = '';
        if ($searchText !== '') {
            // Convert to lower case and escape special characters
            $escapedSearchText = strtolower($searchText);
            // Escape characters: + - ! ( ) { } [ ] ^ " ~ * ? \ :
            $escapedSearchText = preg_replace('/([\+\-!\(\)\{\}\[\]\^"~\*\?\\\\:])/', '\\\\$1', $escapedSearchText);
            // Escape && and ||
            $escapedSearchText = preg_replace('/(&&|\|\|)/', '\\\\$1', $escapedSearchText);
            // Normalize whitespace to single spaces
            $escapedSearchText = preg_replace('/\s+/', ' ', $escapedSearchText);

            // Split into words and join with the AND operator
            $parts = explode(' ', $escapedSearchText);
            return implode(' AND ', $parts);

        }
        return $exactMatchQuery;
    }


    private static function prepareQueryString($searchText): string
    {
        // Extract all quoted phrases
        preg_match_all('/".*?"/', $searchText, $matches);
        $phrases = $matches[0];

        // Check if the search ends with a phrase
        $endWithPhrase = substr($searchText, -1) === '"';

        // Remove phrases and trim extra whitespace
        $searchText = preg_replace('/".*?"/', '', $searchText);
        $searchText = trim(preg_replace('/\s+/', ' ', $searchText));

        // Split remaining search text into words
        $words = $searchText ? explode(' ', $searchText) : [];
        $queryString = '';

        // Escape special characters
        foreach ($words as $i => $w) {
            if (trim($w) === '') continue;
            $w = strtolower($w);
            $w = preg_replace('/([+\-!\(\)\{\}\[\]\^"~\*\?\\\:])/', '\\\\$1', $w);
            $w = preg_replace('/(&&|\|\|)/', '\\\\$1', $w);

            // Add wildcard to last word if query doesn't end with phrase
            if (!$endWithPhrase && $i === count($words) - 1) {
                $w .= '*';
                $queryString .= "+$w ";
            } else {
                $queryString .= "+$w AND ";
            }
        }

        // Escape special characters in phrases
        if (!empty($phrases)) {
            foreach ($phrases as $p) {
                $p = substr($p, 1, -1); // remove quote marks
                $p = preg_replace('/([+\-!\(\)\{\}\[\]\^"~\*\?\\\:])/', '\\\\$1', $p);
                $p = preg_replace('/(&&|\|\|)/', '\\\\$1', $p);
                $queryString .= '+"' . $p . '" ';
            }
        }

        // Enclose the full query in parentheses if not empty
        if (strlen(trim($queryString)) > 0) {
            $queryString = '(' . trim($queryString) . ')';
        }

        return $queryString;
    }
}
