<?php

namespace DIQA\ChemExtension\Eval;

use PHPUnit\Framework\TestCase;

class StructuredOutputTest extends TestCase
{
    public function testBuildSchemaShape(): void
    {
        $schema = TopicSchema::build(['host', 'ka']);

        $this->assertSame('object', $schema['type']);
        $this->assertEqualsCanonicalizing(['summary', 'experiments'], $schema['required']);
        $this->assertFalse($schema['additionalProperties']);

        $item = $schema['properties']['experiments']['items'];
        $this->assertArrayHasKey('host', $item['properties']);
        $this->assertArrayHasKey('ka', $item['properties']);
        $this->assertSame(['string', 'null'], $item['properties']['ka']['type']);
        $this->assertEqualsCanonicalizing(['host', 'ka'], $item['required']);
        $this->assertFalse($item['additionalProperties']);
    }

    public function testParseStructuredResponse(): void
    {
        $json = json_encode([
            'summary' => 'A short summary.',
            'experiments' => [
                ['host' => 'CB[7]', 'ka' => '1.2e9', 'kd' => null],
                ['host' => 'beta-CD', 'ka' => 3000, 'kd' => '3.3e-4'],
            ],
        ]);

        $parsed = StructuredExtractionParser::parse($json);

        $this->assertSame('A short summary.', $parsed['summary']);
        $this->assertCount(2, $parsed['rows']);
        $this->assertSame('CB[7]', $parsed['rows'][0]['host']);
        $this->assertSame('', $parsed['rows'][0]['kd']);      // null -> ''
        $this->assertSame('3000', $parsed['rows'][1]['ka']);  // numeric cast to string
    }

    public function testParseHandlesGarbage(): void
    {
        $this->assertSame([], StructuredExtractionParser::parseRows('not json'));
    }
}
