<?php

namespace DIQA\FacetedSearch2\Utils;

class ArrayTools {

    public static function flatten(array $array): array
    {
        $return = [];
        array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
        return $return;
    }

    public static function arrayFlattenToKeyValues(
        array $deepArray,
        array $accumulator = []
    ): array {
        array_walk($deepArray, function ($value, $key) use (&$accumulator) {
            $accumulator[] = $key;
            if (is_array($value)) {
                $accumulator = self::arrayFlattenToKeyValues($value, $accumulator);
            }
        });

        return $accumulator;
    }
}