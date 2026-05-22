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
     * @return string[] molecule-valued column names for the topic (empty if unknown)
     */
    public static function moleculeFields(string $topic): array
    {
        return self::MOLECULE_FIELDS[$topic] ?? [];
    }
}
