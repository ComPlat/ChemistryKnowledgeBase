<?php

namespace DIQA\ChemExtension\Eval;

use DIQA\ChemExtension\Experiments\ExperimentRepository;
use DIQA\ChemExtension\Utils\ArrayTools;

/**
 * Builds an OpenAI structured-output JSON schema for a topic, so the model returns a strictly
 * typed object instead of free-form CSV. This removes the whole class of parse / column-drift /
 * missing-fence failures — orthogonal to prompt wording.
 *
 * The schema is { "summary": string, "experiments": [ { <field>: string|null, ... } ] } where the
 * experiment fields are the row-template parameter names of the topic (the same vocabulary the
 * CSV columns and the gold set use).
 */
class TopicSchema
{
    /**
     * Builds the JSON schema from an explicit list of experiment field names (pure / testable).
     *
     * @param string[] $fields experiment column names
     */
    public static function build(array $fields): array
    {
        $properties = [];
        foreach ($fields as $field) {
            $properties[$field] = ['type' => ['string', 'null']];
        }
        // strict mode requires every property to be listed in "required" and additionalProperties=false
        $experimentItem = [
            'type' => 'object',
            'properties' => $properties,
            'required' => array_values($fields),
            'additionalProperties' => false,
        ];

        return [
            'type' => 'object',
            'properties' => [
                'summary' => ['type' => ['string', 'null']],
                'experiments' => ['type' => 'array', 'items' => $experimentItem],
            ],
            'required' => ['summary', 'experiments'],
            'additionalProperties' => false,
        ];
    }

    /**
     * Resolves the experiment field names for a topic's investigation form from its row template
     * (requires the MediaWiki/SMW runtime). Excludes auto-generated and bookkeeping fields.
     *
     * @return string[]
     */
    public static function fieldsForForm(string $form): array
    {
        $type = ExperimentRepository::getInstance()->getExperimentType($form);
        $params = ArrayTools::flatten(array_values($type->getProperties()));

        $fields = [];
        foreach ($params as $param) {
            if (str_starts_with($param, 'auto-generated-') || in_array($param, ['BasePageName', 'include'], true)) {
                continue;
            }
            if (!in_array($param, $fields, true)) {
                $fields[] = $param;
            }
        }
        return $fields;
    }
}
