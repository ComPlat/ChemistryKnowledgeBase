<?php

namespace DIQA\ChemExtension\Utils;

class HtmlTools {

    public static function formatSumFormula($formula) {
        return preg_replace('/(\d+)/', '<sub>$1</sub>', $formula);
    }
}