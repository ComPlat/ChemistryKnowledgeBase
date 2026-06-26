<?php

namespace DIQA\FacetedSearch2\SolrClient;

use DIQA\FacetedSearch2\Model\Common\Datatype;

class Helper
{
    private const RELATION_REGEX = "/^smwh_(.+)_(t|s)$/";
    private const ATTRIBUTE_REGEX = "/^smwh_(.+)_xsdvalue_(.+)$/";
    private const ATTRIBUTE_REGEX_DATEVALUE = "/^smwh_(.+)_(datevalue_l)$/";
    /**
     * Helper functions to return implementation specific property/value suffixes.
     * dependant from backend
     */
    private const DatatypeSuffixForSearchMap = [
        Datatype::STRING => 'xsdvalue_s',
        Datatype::NUMBER => 'xsdvalue_d',
        Datatype::BOOLEAN => 'xsdvalue_b',
        Datatype::WIKIPAGE => 's',
        Datatype::DATETIME => 'datevalue_l'
    ];

    private const DatatypeSuffixForPropertyMap = [
        Datatype::STRING => 'xsdvalue_t',
        Datatype::NUMBER => 'xsdvalue_d',
        Datatype::BOOLEAN => 'xsdvalue_b',
        Datatype::WIKIPAGE => 't',
        Datatype::DATETIME => 'xsdvalue_dt'
    ];

    public static function generateSOLRProperty(string $title, $type)
    {
        $s = self::encodeCharsInProperties($title);
        return "smwh_{$s}_" . self::DatatypeSuffixForPropertyMap[$type];
    }

    public static function generateSOLRPropertyForSearch(string $title, $type)
    {
        $s = self::encodeCharsInProperties($title);
        return "smwh_{$s}_" . self::DatatypeSuffixForSearchMap[$type];
    }

    private static function decodeCharsInProperty(string $title)
    {
        $s = $title;
        $s = self::decodeSpecialChars($s);
        return preg_replace('/__/', ' ', $s);
    }

    private static function encodeSpecialChars($s)
    {
        return str_replace("%", "_0x", urlencode($s));
    }

    private static function decodeSpecialChars($s)
    {
        return urldecode(str_replace("_0x", "%", $s));
    }

    private static function encodeCharsInProperties(string $title)
    {
        $s = $title;
        $s = preg_replace('/_/', '__', $s);
        $s = preg_replace('/\s/', '__', $s);
        return self::encodeSpecialChars($s);
    }

    public static function parseSOLRProperty(string $property)
    {
        $num = preg_match_all(self::ATTRIBUTE_REGEX, $property, $nameType);
        if ($num === 0) {
            // maybe a relation facet
            $num = preg_match_all(self::RELATION_REGEX, $property, $nameType);
            if ($num > 0) {
                $name = $nameType[1][0];
                $name = Helper::decodeCharsInProperty($name);
                return [$name, Datatype::WIKIPAGE];
            }
            $num = preg_match_all(self::ATTRIBUTE_REGEX_DATEVALUE, $property, $nameType);
            if ($num > 0) {
                $name = $nameType[1][0];
                $name = Helper::decodeCharsInProperty($name);
                return [$name, Datatype::DATETIME];
            }
            return null;
        }
        $name = $nameType[1][0];
        $name = Helper::decodeCharsInProperty($name);
        $type = $nameType[2][0];
        switch ($type) {
            case 'd':
            case 'i':
                // numeric
                return [$name, Datatype::NUMBER];
            case 'dt':
            case 'datevalue_l':
                // date
                return [$name, Datatype::DATETIME];
            case 'b':
                // boolean
                return [$name, Datatype::BOOLEAN];
            case 's':
            case 't':
                // string or anything else
            default:
                return [$name, Datatype::STRING];
        }


    }

    public static function quoteValue($v, $type)
    {
        if ($type === Datatype::NUMBER) {
            return $v;
        }
        if ($type === Datatype::BOOLEAN) {
            return $v ? 'true' : 'false';
        }
        return '"' . preg_replace('/"/', '\"', $v) . '"';
    }

    public static function convertDateTimeToLong($date): string
    {
        $datetime = \DateTime::createFromFormat('Y-m-d\TH:i:s+', $date);
        return $datetime->format('YmdHis');
    }

    public static function getSOLRBaseUrl(): string
    {

        global $fs2gBackendConfig;

        $host = $fs2gBackendConfig['host'] ?? 'localhost';
        $port = $fs2gBackendConfig['port'] ?? 8983;
        $indexName = $fs2gBackendConfig['indexName'] ?? 'mw';

        $protocol = "http";
        return "$protocol://$host:$port/solr/$indexName";
    }

    public static function getBasicAuthHeader(): array
    {
        global $fs2gBackendConfig;

        $user = $fs2gBackendConfig['user'] ?? '';
        $pass = $fs2gBackendConfig['pass'] ?? '';

        if ($user !== '') {
            return ["Authorization: Basic " . base64_encode("$user:$pass")];
        }
        return [];
    }
}
