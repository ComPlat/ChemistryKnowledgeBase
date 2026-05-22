<?php

namespace DIQA\ChemExtension\Eval;

use PHPUnit\Framework\TestCase;

/**
 * Tests the topic-agnostic override layer (profile.json). The generic derivation from wiki
 * templates needs the MediaWiki runtime and is not exercised here.
 */
class TopicProfileTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir() . '/chemext_eval_' . uniqid();
        mkdir($this->baseDir . '/NewTopic', 0777, true);
    }

    protected function tearDown(): void
    {
        @unlink($this->baseDir . '/NewTopic/profile.json');
        @rmdir($this->baseDir . '/NewTopic');
        @rmdir($this->baseDir);
    }

    private function writeProfile(array $profile): void
    {
        file_put_contents($this->baseDir . '/NewTopic/profile.json', json_encode($profile));
    }

    public function testOverridesAreUsedForUnknownTopic(): void
    {
        $this->writeProfile([
            'fields' => ['host', 'guest', 'ka'],
            'moleculeFields' => ['host', 'guest'],
            'expectedUnits' => [
                'ka' => ['unit' => 'M-1', 'family' => UnitConverter::F_ASSOCIATION],
            ],
        ]);

        $profile = TopicProfile::forTopic('NewTopic', $this->baseDir);

        $this->assertSame(['host', 'guest', 'ka'], $profile->fields());
        $this->assertSame(['host', 'guest'], $profile->moleculeFields());
        $this->assertSame(['unit' => 'M-1', 'family' => UnitConverter::F_ASSOCIATION], $profile->expectedUnits()['ka']);
    }

    public function testSanityRulesAreDerivedFromUnits(): void
    {
        $this->writeProfile([
            'fields' => ['fe__CO', 'fe__H2', 'ka'],
            'expectedUnits' => [
                'fe__CO' => ['unit' => '%', 'family' => UnitConverter::F_PERCENT],
                'fe__H2' => ['unit' => '%', 'family' => UnitConverter::F_PERCENT],
                'ka' => ['unit' => 'M-1', 'family' => UnitConverter::F_ASSOCIATION],
            ],
        ]);

        $rules = TopicProfile::forTopic('NewTopic', $this->baseDir)->sanityRules();

        $this->assertContains('fe__CO', $rules['percentage']);
        $this->assertContains('ka', $rules['positive']);
        // two per-product percentages with the same prefix => a sum<=100 rule
        $this->assertNotEmpty($rules['sumAtMost']);
        $this->assertSame('fe', $rules['sumAtMost'][0]['label']);
    }

    public function testSanityRuleOverrideWins(): void
    {
        $this->writeProfile([
            'sanityRules' => ['nonNegative' => ['ton'], 'positive' => [], 'percentage' => [], 'sumAtMost' => []],
        ]);
        $rules = TopicProfile::forTopic('NewTopic', $this->baseDir)->sanityRules();
        $this->assertSame(['ton'], $rules['nonNegative']);
    }
}
