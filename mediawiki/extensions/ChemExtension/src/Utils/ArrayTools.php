<?php
namespace DIQA\ChemExtension\Utils;

class ArrayTools {

    public static function transpose($arr): array
    {
        $result = [];
        $keys = array_keys($arr);
        for ($row = 0,  $rows = count(reset($arr)); $row < $rows; $row++) {
            foreach ($keys as $key) {
                $result[$row][$key] = $arr[$key][$row];
            }
        }
        return $result;
    }

    public static function propertiesToArray($obj) {
        $result = [];
        foreach($obj as $property => $value) {
            $result[$property] = $value;
        }
        return $result;
    }

    public static function getFirstIfArray($value) {
        return is_array($value) ? reset($value):$value;
    }

    public static function flatten(array $array): array
    {
        $return = [];
        array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
        return $return;
    }
}