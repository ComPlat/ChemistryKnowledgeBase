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

}