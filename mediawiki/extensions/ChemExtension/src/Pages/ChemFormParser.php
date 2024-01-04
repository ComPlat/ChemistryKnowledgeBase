<?php

namespace DIQA\ChemExtension\Pages;

class ChemFormParser
{
    const MAX_RGROUPS = 100;
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
                $formulas[$i],
                trim($attributes['smiles']) ?? '',
                trim($attributes['inchi']) ?? '',
                trim($attributes['inchikey']) ?? '',
                trim($attributes['width']) ?? '',
                trim($attributes['height']) ?? '',
                trim($attributes['float']) ?? '',
                self::parseRGroups($attributes));
        }
        return $results;
    }

    public static function parseRGroups(array $attributes) : array {
        $result = [];
        for($i = 1; $i < self::MAX_RGROUPS; $i++) {
            if (!array_key_exists("r$i", $attributes)) {
                continue;
            }
            $rGroups = explode(',', $attributes["r$i"]);
            $rGroups = array_map(function ($e) {
                return trim($e);
            }, $rGroups);
            $result["r$i"] = $rGroups;
        }

        return $result;
    }

    public function replaceChemForm($moleculeKey, $wikitext, ChemForm $chemFormToReplace): string {
        return preg_replace_callback(self::CHEM_FORM_REGEX, function($matches) use ($moleculeKey, $chemFormToReplace) {
            $parser = new ChemFormParser();
            $chemForm = $parser->parse($matches[0])[0];
            if ($chemForm->getMoleculeKey() === $moleculeKey) {
                $chemFormToReplace->merge($chemForm);
                return $chemFormToReplace->serializeAsWikitext();
            } else {
                return $matches[0];

            }
        }, $wikitext);
    }

    private function parseAttributes($attributeString): array
    {

        preg_match_all(self::ATTRIBUTES, $attributeString, $matches);
        $keys = $matches[1] ?? [];
        $values = $matches[2] ?? [];

        $result = [];
        for ($i = 0; $i < count($keys); $i++) {
            $result[$keys[$i]] = html_entity_decode($values[$i]);
        }

        return $result;
    }
}