<?php

namespace DIQA\FacetedSearch2\SolrClient;

class Util {
    public static function splitResponse($res): array
    {
        $bodyBegin = strpos($res, "\r\n\r\n");
        list($header, $res) = $bodyBegin !== false ? array(substr($res, 0, $bodyBegin), substr($res, $bodyBegin + 4)) : array($res, "");
        return array($header, str_replace("%0A%0D%0A%0D", "\r\n\r\n", $res));
    }

    public static function buildQueryParams(array $params): string
    {
        $encodedParams = [];

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $encodedParams = array_merge($encodedParams, array_map(fn($e) => "$key=" . urlencode($e), $value));
            } else {
                $encodedParams[] = "$key=" . urlencode($value);
            }
        }

        return implode("&", $encodedParams);
    }


}
