<?php

namespace DIQA\ChemExtension\Eval;

use DIQA\ChemExtension\Experiments\ExperimentRepository;
use DIQA\ChemExtension\ParserFunctions\ConvertQuantity;
use DIQA\ChemExtension\Utils\ArrayTools;
use DIQA\ChemExtension\Utils\WikiTools;
use MediaWiki\Title\Title;
use Throwable;

/**
 * Single, topic-agnostic source of everything the evaluation/optimizer needs to know about a
 * topic: its experiment fields, which of them are molecules, the expected unit per numeric field,
 * and the plausibility (sanity) rules.
 *
 * The design is "generic core + per-topic fine-tuning":
 *  1. Everything is *derived generically* from the wiki itself — fields and molecule columns from
 *     the investigation row template, units from the SMW property/default-unit definitions. So a
 *     brand-new topic works out of the box, with no code change.
 *  2. A per-topic override file `eval/<Topic>/profile.json` refines the derived defaults (e.g. fix
 *     a mis-detected unit family, add a sanity rule, pin the investigation form). This is the
 *     "fine-tuning per topic" layer and needs no code either.
 *  3. Built-in defaults for the originally supported topics act as a fallback.
 *
 * Precedence (low → high): derived  <  built-in defaults  <  profile.json override.
 */
class TopicProfile
{
    private string $topic;
    private array $overrides;

    private function __construct(string $topic, array $overrides)
    {
        $this->topic = $topic;
        $this->overrides = $overrides;
    }

    public static function forTopic(string $topic, ?string $evalBaseDir = null): self
    {
        $base = $evalBaseDir ?? dirname(__DIR__, 2) . '/eval';
        $profileFile = $base . '/' . $topic . '/profile.json';
        $overrides = [];
        if (is_file($profileFile)) {
            $decoded = json_decode(file_get_contents($profileFile), true);
            if (is_array($decoded)) {
                $overrides = $decoded;
            }
        }
        return new self($topic, $overrides);
    }

    public function formName(): ?string
    {
        return $this->overrides['form'] ?? EvalTopicConfig::formName($this->topic);
    }

    /** Experiment column names. */
    public function fields(): array
    {
        if (isset($this->overrides['fields']) && is_array($this->overrides['fields'])) {
            return $this->overrides['fields'];
        }
        $form = $this->formName();
        if ($form === null) {
            return [];
        }
        try {
            return TopicSchema::fieldsForForm($form);
        } catch (Throwable $e) {
            return [];
        }
    }

    /** Columns to compare by molecule identity. */
    public function moleculeFields(): array
    {
        if (isset($this->overrides['moleculeFields']) && is_array($this->overrides['moleculeFields'])) {
            return $this->overrides['moleculeFields'];
        }
        $derived = $this->deriveMoleculeFields();
        if (!empty($derived)) {
            return $derived;
        }
        return EvalTopicConfig::moleculeFields($this->topic);
    }

    /**
     * Expected unit + dimension per numeric field.
     *
     * @return array<string, array{unit:string, family:string}>
     */
    public function expectedUnits(): array
    {
        $units = $this->deriveExpectedUnits();
        $units = array_merge($units, EvalTopicConfig::expectedUnits($this->topic));
        if (isset($this->overrides['expectedUnits']) && is_array($this->overrides['expectedUnits'])) {
            foreach ($this->overrides['expectedUnits'] as $field => $spec) {
                if (isset($spec['unit'], $spec['family'])) {
                    $units[$field] = ['unit' => $spec['unit'], 'family' => $spec['family']];
                }
            }
        }
        return $units;
    }

    /**
     * Plausibility rules, derived from the expected-unit families plus naming heuristics, then
     * refined by the profile override.
     *
     * @return array{nonNegative:string[], positive:string[], percentage:string[], sumAtMost:array}
     */
    public function sanityRules(): array
    {
        $rules = $this->deriveSanityRules();
        if (isset($this->overrides['sanityRules']) && is_array($this->overrides['sanityRules'])) {
            foreach (['nonNegative', 'positive', 'percentage', 'sumAtMost'] as $key) {
                if (isset($this->overrides['sanityRules'][$key]) && is_array($this->overrides['sanityRules'][$key])) {
                    $rules[$key] = $this->overrides['sanityRules'][$key];
                }
            }
        }
        return $rules;
    }

    /** JSON schema for structured output. */
    public function jsonSchema(): array
    {
        return TopicSchema::build($this->fields());
    }

    // --- generic derivation from the wiki -------------------------------------------------

    private function deriveMoleculeFields(): array
    {
        try {
            $form = $this->formName();
            if ($form === null) {
                return [];
            }
            $rowTemplate = ExperimentRepository::getInstance()->getExperimentType($form)->getRowTemplate();
            $text = WikiTools::getText(Title::newFromText($rowTemplate, NS_TEMPLATE));
            // params wrapped in {{DisplayMolecule|{{{param ...}}} }}
            preg_match_all('/\{\{DisplayMolecule\|\s*\{\{\{([^|}]+)/', $text, $matches);
            $fields = [];
            foreach ($matches[1] as $param) {
                $param = trim($param);
                if ($param !== '' && !in_array($param, $fields, true)) {
                    $fields[] = $param;
                }
            }
            return $fields;
        } catch (Throwable $e) {
            return [];
        }
    }

    private function deriveExpectedUnits(): array
    {
        try {
            $form = $this->formName();
            if ($form === null) {
                return [];
            }
            $type = ExperimentRepository::getInstance()->getExperimentType($form);
            $paramToProperty = [];
            foreach ($type->getProperties() as $property => $params) {
                foreach (ArrayTools::flatten([$params]) as $param) {
                    if (!isset($paramToProperty[$param])) {
                        $paramToProperty[$param] = $property;
                    }
                }
            }
            $units = [];
            foreach ($paramToProperty as $param => $property) {
                $unit = ConvertQuantity::getDefaultUnit($property, $form);
                if (!is_string($unit) || $unit === '') {
                    continue;
                }
                $family = UnitConverter::familyForUnit($unit);
                if ($family !== null) {
                    $units[$param] = ['unit' => $unit, 'family' => $family];
                }
            }
            return $units;
        } catch (Throwable $e) {
            return [];
        }
    }

    private function deriveSanityRules(): array
    {
        $rules = ['nonNegative' => [], 'positive' => [], 'percentage' => [], 'sumAtMost' => []];
        $expected = $this->expectedUnits();

        $percentByPrefix = [];
        foreach ($expected as $field => $spec) {
            $family = $spec['family'];
            switch ($family) {
                case UnitConverter::F_PERCENT:
                    $rules['percentage'][] = $field;
                    $prefix = explode('__', $field)[0];
                    $percentByPrefix[$prefix][] = $field;
                    break;
                case UnitConverter::F_ASSOCIATION:
                    $rules['positive'][] = $field;
                    break;
                case UnitConverter::F_CONCENTRATION:
                case UnitConverter::F_TIME:
                case UnitConverter::F_FREQUENCY:
                case UnitConverter::F_WAVELENGTH:
                case UnitConverter::F_CURRENT_DENSITY:
                    $rules['nonNegative'][] = $field;
                    break;
            }
        }

        // turnover numbers / yields are non-negative even without a unit
        foreach ($this->fields() as $field) {
            if (preg_match('/turnover_number|turnover_frequency|quantum_yield/i', $field)
                && !in_array($field, $rules['nonNegative'], true)
                && !in_array($field, $rules['percentage'], true)) {
                $rules['nonNegative'][] = $field;
            }
        }

        // groups of per-product percentages (e.g. faradaic_efficiency__*) should sum to <= 100
        foreach ($percentByPrefix as $prefix => $group) {
            if (count($group) >= 2) {
                $rules['sumAtMost'][] = ['label' => $prefix, 'max' => 100.0, 'fields' => $group];
            }
        }

        return $rules;
    }
}
