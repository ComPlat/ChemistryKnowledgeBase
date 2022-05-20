<?php

namespace DIQA\ChemExtension\Pages;

class ChemFormParser
{

    const CHEM_FORM_REGEX = '/<chemform([^>]*)>([^<]*)<\/chemform>/m';
    const ATTRIBUTES = '/(\w+)="([^"]*)"/m';

    public function parse($wikitext): array
    {

        preg_match_all(self::CHEM_FORM_REGEX, $wikitext, $matches);
        $attributeStrings = $matches[1] ?? [];
        $formulas = $matches[2] ?? [];

        $results = [];
        for ($i = 0; $i < count($formulas); $i++) {
            $attributes = $this->parseAttributes($attributeStrings[$i]);
            $results[] = new ChemForm(
                $attributes['id'],
                $formulas[$i],
                $attributes['isReaction'],
                $attributes['smiles'],
                $attributes['width'],
                $attributes['height'],
                $attributes['float']);
        }
        return $results;
    }

    private function parseAttributes($attributeString): array
    {

        preg_match_all(self::ATTRIBUTES, $attributeString, $matches);
        $keys = $matches[1] ?? [];
        $values = $matches[2] ?? [];

        $result = [];
        for ($i = 0; $i < count($keys); $i++) {
            $result[$keys[$i]] = $values[$i];
        }

        return $result;
    }
}