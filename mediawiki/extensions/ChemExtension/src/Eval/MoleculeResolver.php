<?php

namespace DIQA\ChemExtension\Eval;

use DIQA\ChemExtension\Utils\QueryUtils;

/**
 * Canonicalizes a molecule name to its Molecule page id, so the scorer can compare molecule
 * fields (catalyst, photosensitizer, host, guest, solvents, ...) by identity rather than by
 * raw string. This avoids penalizing valid synonyms / abbreviations (e.g. "CB[7]" vs
 * "cucurbit[7]uril") that resolve to the same molecule in the wiki.
 *
 * Mirrors the lookup used by
 * {@see \DIQA\ChemExtension\PublicationImport\ExperimentWikitextImporter::searchForMolecule()}.
 *
 * Requires the Semantic MediaWiki query stack, so it only works inside the MediaWiki runtime
 * (the maintenance loop), not in plain unit tests — there a null resolver is used instead.
 */
class MoleculeResolver
{
    /** @var array<string,string> in-process cache: normalized name -> canonical key */
    private array $cache = [];

    /**
     * Returns a canonical key for the given molecule name: "Molecule:<id>" when it resolves to a
     * known molecule, otherwise the normalized input string.
     */
    public function canonicalize(string $name): string
    {
        $key = mb_strtolower(trim($name));
        if ($key === '') {
            return '';
        }
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $resolved = $this->lookup($name) ?? $key;
        $this->cache[$key] = $resolved;
        return $resolved;
    }

    private function lookup(string $searchText): ?string
    {
        $searchText = trim($searchText);
        if ($searchText === '' || is_numeric($searchText)) {
            return null;
        }
        $query = <<<QUERY
[[Category:Molecule]][[Synonym::~*$searchText*]]
OR [[Category:Molecule]][[Abbreviation::~*$searchText*]]
OR [[Category:Molecule]][[Trivialname::~*$searchText*]]
OR [[Category:Molecule]][[IUPACName::~*$searchText*]]
QUERY;
        $results = QueryUtils::executeBasicQuery($query);
        if ($results->getCount() === 0) {
            return null;
        }
        $row = $results->getNext();
        $column = reset($row);
        if ($column === false) {
            return null;
        }
        $dataItem = $column->getNextDataItem();
        if ($dataItem === false || $dataItem === null) {
            return null;
        }
        return 'Molecule:' . $dataItem->getTitle()->getText();
    }
}
