<?php

namespace DIQA\ChemExtension\Eval;

use PHPUnit\Framework\TestCase;

class UnitConverterTest extends TestCase
{
    public function testParseSplitsNumberAndUnit(): void
    {
        $p = UnitConverter::parse('1.2e9 M-1');
        $this->assertEqualsWithDelta(1.2e9, $p['number'], 1.0);
        $this->assertSame('m-1', $p['unit']);

        $p2 = UnitConverter::parse('25 °C');
        $this->assertEqualsWithDelta(25.0, $p2['number'], 1e-9);
        $this->assertSame('c', $p2['unit']);

        $p3 = UnitConverter::parse('298');
        $this->assertEqualsWithDelta(298.0, $p3['number'], 1e-9);
        $this->assertSame('', $p3['unit']);
    }

    public function testConcentrationConversion(): void
    {
        $this->assertEqualsWithDelta(1e-6, UnitConverter::convertWithinFamily(1.0, 'uM', 'M', UnitConverter::F_CONCENTRATION), 1e-18);
        $this->assertEqualsWithDelta(1000.0, UnitConverter::convertWithinFamily(1.0, 'M', 'mM', UnitConverter::F_CONCENTRATION), 1e-9);
    }

    public function testTemperatureIsAffine(): void
    {
        $this->assertEqualsWithDelta(298.15, UnitConverter::convertWithinFamily(25.0, 'C', 'K', UnitConverter::F_TEMPERATURE), 1e-9);
        $this->assertEqualsWithDelta(0.0, UnitConverter::convertWithinFamily(273.15, 'K', 'C', UnitConverter::F_TEMPERATURE), 1e-9);
    }

    public function testEmptyFromUnitAssumesTargetUnit(): void
    {
        // a bare number is taken to be already in the expected unit
        $this->assertEqualsWithDelta(5.0, UnitConverter::convertWithinFamily(5.0, '', 'uM', UnitConverter::F_CONCENTRATION), 1e-12);
    }

    public function testCrossFamilyReturnsNull(): void
    {
        $this->assertNull(UnitConverter::convertWithinFamily(5.0, 'V', 'M', UnitConverter::F_CONCENTRATION));
    }

    public function testInFamily(): void
    {
        $this->assertTrue(UnitConverter::inFamily('mV', UnitConverter::F_POTENTIAL));
        $this->assertTrue(UnitConverter::inFamily('', UnitConverter::F_POTENTIAL)); // no unit = assumed ok
        $this->assertFalse(UnitConverter::inFamily('M', UnitConverter::F_POTENTIAL));
    }
}
