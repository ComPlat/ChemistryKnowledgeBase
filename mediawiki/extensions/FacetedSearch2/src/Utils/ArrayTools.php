<?php

namespace DIQA\FacetedSearch2\Utils;

class ArrayTools {

    public static function flatten(array $array): array
    {
        $return = [];
        array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
        return $return;
    }
}