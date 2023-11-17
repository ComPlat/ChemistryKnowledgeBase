<?php
namespace DIQA\Formatter;

trait Tools {

    static function normalize($output) {
        return str_replace("\r", "", $output);
    }

    /**
     * Highlights <$substring> in $text with the given color.
     *
     * @param $text
     * @param $substring
     * @param $color
     * @return array|string|string[]
     */
    static function highlightWithColor($text, $substring, $color) {
        return str_replace("<$substring>", $color->getColorString()."$substring"."\033[0m", $text);
    }
}