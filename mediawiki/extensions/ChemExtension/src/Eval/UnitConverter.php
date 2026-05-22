<?php

namespace DIQA\ChemExtension\Eval;

/**
 * Small, self-contained unit converter for the quantities that appear in the extraction tables.
 *
 * Used by {@see ExtractionScorer} to (a) compare numeric cells unit-aware — so "1 µM" and
 * "1e-6 M" count as equal — and (b) flag dimensionally wrong units (e.g. a potential reported in
 * a concentration unit). It is deliberately independent of the SMW quantity machinery so it stays
 * predictable and unit-testable; the expected unit + family per field come from
 * {@see EvalTopicConfig}.
 *
 * Families use multiplicative factors to a base unit, except temperature which is affine.
 */
class UnitConverter
{
    public const F_CONCENTRATION = 'concentration';
    public const F_POTENTIAL = 'potential';
    public const F_TIME = 'time';
    public const F_ENERGY_MOL = 'energy_per_mol';
    public const F_WAVELENGTH = 'wavelength';
    public const F_ASSOCIATION = 'association';
    public const F_FREQUENCY = 'frequency';
    public const F_PERCENT = 'percent';
    public const F_CURRENT_DENSITY = 'current_density';
    public const F_TEMPERATURE = 'temperature';

    /** family => [ normalized unit => factor to the family base unit ] */
    private const FAMILIES = [
        self::F_CONCENTRATION => ['m' => 1.0, 'mm' => 1e-3, 'um' => 1e-6, 'nm' => 1e-9, 'pm' => 1e-12, 'mol/l' => 1.0, 'mmol/l' => 1e-3, 'umol/l' => 1e-6],
        self::F_POTENTIAL => ['v' => 1.0, 'mv' => 1e-3],
        self::F_TIME => ['h' => 1.0, 'hr' => 1.0, 'min' => 1.0 / 60.0, 's' => 1.0 / 3600.0, 'sec' => 1.0 / 3600.0, 'd' => 24.0],
        self::F_ENERGY_MOL => ['kj/mol' => 1.0, 'kcal/mol' => 4.184, 'j/mol' => 1e-3],
        self::F_WAVELENGTH => ['nm' => 1.0, 'um' => 1000.0, 'pm' => 1e-3, 'a' => 0.1],
        self::F_ASSOCIATION => ['m-1' => 1.0, 'mm-1' => 1e3, 'um-1' => 1e6],
        self::F_FREQUENCY => ['h-1' => 1.0, 'min-1' => 60.0, 's-1' => 3600.0],
        self::F_PERCENT => ['%' => 1.0, 'percent' => 1.0],
        self::F_CURRENT_DENSITY => ['ma/cm2' => 1.0, 'a/cm2' => 1000.0, 'ua/cm2' => 1e-3],
    ];

    /**
     * Splits a raw cell into its leading number and trailing unit token (normalized).
     *
     * @return array{number: float|null, unit: string}
     */
    public static function parse(string $raw): array
    {
        $raw = trim($raw);
        if (preg_match('/^[+-]?\d+(?:[.,]\d+)?(?:[eE][-+]?\d+)?/', $raw, $m)) {
            $number = (float) str_replace(',', '.', $m[0]);
            $unit = self::normalizeUnit(substr($raw, strlen($m[0])));
            return ['number' => $number, 'unit' => $unit];
        }
        return ['number' => null, 'unit' => self::normalizeUnit($raw)];
    }

    /**
     * Converts a value from $fromUnit to $toUnit within $family.
     * An empty $fromUnit means "already in $toUnit". Returns null if a unit is not part of the family.
     */
    public static function convertWithinFamily(float $value, string $fromUnit, string $toUnit, string $family): ?float
    {
        $from = self::normalizeUnit($fromUnit);
        $to = self::normalizeUnit($toUnit);
        if ($from === '') {
            $from = $to;
        }

        if ($family === self::F_TEMPERATURE) {
            $kelvin = self::toKelvin($value, $from);
            if ($kelvin === null) {
                return null;
            }
            return self::fromKelvin($kelvin, $to);
        }

        $map = self::FAMILIES[$family] ?? null;
        if ($map === null || !isset($map[$from], $map[$to])) {
            return null;
        }
        return $value * $map[$from] / $map[$to];
    }

    /**
     * True if the (possibly empty) unit is dimensionally consistent with the family.
     * An empty unit is treated as consistent (the value is assumed to be in the expected unit).
     */
    public static function inFamily(string $unit, string $family): bool
    {
        $u = self::normalizeUnit($unit);
        if ($u === '') {
            return true;
        }
        if ($family === self::F_TEMPERATURE) {
            return $u === 'k' || $u === 'c';
        }
        return isset(self::FAMILIES[$family][$u]);
    }

    private static function toKelvin(float $value, string $unit): ?float
    {
        if ($unit === '' || $unit === 'k') {
            return $value;
        }
        if ($unit === 'c') {
            return $value + 273.15;
        }
        return null;
    }

    private static function fromKelvin(float $kelvin, string $unit): ?float
    {
        if ($unit === '' || $unit === 'k') {
            return $kelvin;
        }
        if ($unit === 'c') {
            return $kelvin - 273.15;
        }
        return null;
    }

    private static function normalizeUnit(string $unit): string
    {
        $unit = mb_strtolower(trim($unit));
        $unit = str_replace(["\u{00b5}", "\u{03bc}"], 'u', $unit); // micro sign / greek mu -> u
        $unit = str_replace(['°', '^', ' ', '·', '⋅', '*'], '', $unit);
        $unit = str_replace('degc', 'c', $unit);
        return $unit;
    }
}
