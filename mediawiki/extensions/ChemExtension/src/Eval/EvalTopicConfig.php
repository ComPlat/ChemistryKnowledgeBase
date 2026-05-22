<?php

namespace DIQA\ChemExtension\Eval;

/**
 * Per-topic evaluation configuration.
 *
 * Currently lists which CSV columns are molecule-valued, so {@see ExtractionScorer} can compare
 * them by molecule identity (via {@see MoleculeResolver}) instead of raw string. The field names
 * are the row-template parameter names (the ones wrapped in {{DisplayMolecule|...}} in the
 * corresponding wikischema/Template/<rowTemplate>.wiki).
 *
 * Keyed by the topic directory name under eval/ (underscored).
 */
class EvalTopicConfig
{
    private const MOLECULE_FIELDS = [
        'Host_Guest_interaction' => ['host', 'guest', 'cofactor'],
        'Photocatalytic_CO2_conversion' => ['catalyst', 'PS', 'e-D', 'H-D', 'solvent A', 'solvent B', 'solvent C'],
        'Electrochemical_CO2_conversion' => [
            'catalyst',
            'solvent_A__WE', 'solvent_B__WE', 'solvent_C__WE',
            'solvent_A__CE', 'solvent_B__CE', 'solvent_C__CE',
        ],
    ];

    /**
     * Expected unit + dimension per numeric field, derived from the column unit hints in the
     * MediaWiki:Prompt_import_<Topic> pages. Used for unit-aware comparison and the unit-correctness
     * metric in {@see ExtractionScorer}. Values are reported unitless in these units, but the model
     * sometimes appends a (possibly different) unit — this lets us normalize and check it.
     *
     * @var array<string, array<string, array{unit:string, family:string}>>
     */
    private const EXPECTED_UNITS = [
        'Host_Guest_interaction' => [
            'host conc' => ['unit' => 'M', 'family' => UnitConverter::F_CONCENTRATION],
            'guest conc' => ['unit' => 'M', 'family' => UnitConverter::F_CONCENTRATION],
            'cofactor conc' => ['unit' => 'M', 'family' => UnitConverter::F_CONCENTRATION],
            'ka' => ['unit' => 'M-1', 'family' => UnitConverter::F_ASSOCIATION],
            'kd' => ['unit' => 'M', 'family' => UnitConverter::F_CONCENTRATION],
            'deltaG' => ['unit' => 'kJ/mol', 'family' => UnitConverter::F_ENERGY_MOL],
            'temperature' => ['unit' => 'K', 'family' => UnitConverter::F_TEMPERATURE],
        ],
        'Photocatalytic_CO2_conversion' => [
            'cat conc' => ['unit' => 'uM', 'family' => UnitConverter::F_CONCENTRATION],
            'PS conc' => ['unit' => 'mM', 'family' => UnitConverter::F_CONCENTRATION],
            'e-D conc' => ['unit' => 'M', 'family' => UnitConverter::F_CONCENTRATION],
            'H-D conc' => ['unit' => 'M', 'family' => UnitConverter::F_CONCENTRATION],
            'Temperature' => ['unit' => 'C', 'family' => UnitConverter::F_TEMPERATURE],
            'λexc' => ['unit' => 'nm', 'family' => UnitConverter::F_WAVELENGTH],
            'irr time' => ['unit' => 'h', 'family' => UnitConverter::F_TIME],
            'Turnover_frequency__CO' => ['unit' => 'h-1', 'family' => UnitConverter::F_FREQUENCY],
            'Quantum_yield__CO' => ['unit' => '%', 'family' => UnitConverter::F_PERCENT],
        ],
        'Electrochemical_CO2_conversion' => [
            'current density' => ['unit' => 'mA/cm2', 'family' => UnitConverter::F_CURRENT_DENSITY],
            'cathodic potential' => ['unit' => 'V', 'family' => UnitConverter::F_POTENTIAL],
            'electrolysis duration' => ['unit' => 'h', 'family' => UnitConverter::F_TIME],
            'faradaic_efficiency__CO' => ['unit' => '%', 'family' => UnitConverter::F_PERCENT],
            'faradaic_efficiency__HCOOH' => ['unit' => '%', 'family' => UnitConverter::F_PERCENT],
            'faradaic_efficiency__H2' => ['unit' => '%', 'family' => UnitConverter::F_PERCENT],
            'faradaic_efficiency__CH4' => ['unit' => '%', 'family' => UnitConverter::F_PERCENT],
        ],
    ];

    /**
     * @return string[] molecule-valued column names for the topic (empty if unknown)
     */
    public static function moleculeFields(string $topic): array
    {
        return self::MOLECULE_FIELDS[$topic] ?? [];
    }

    /**
     * @return array<string, array{unit:string, family:string}> expected unit per numeric field
     */
    public static function expectedUnits(string $topic): array
    {
        return self::EXPECTED_UNITS[$topic] ?? [];
    }
}
