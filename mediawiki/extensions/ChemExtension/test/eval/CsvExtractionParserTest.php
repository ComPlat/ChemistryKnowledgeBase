<?php

namespace DIQA\ChemExtension\Eval;

use PHPUnit\Framework\TestCase;

class CsvExtractionParserTest extends TestCase
{
    public function testParsesFencedCsvAndStripsUnitHints(): void
    {
        $response = <<<TEXT
Here is the summary.

```csv
host , host conc [M] , guest , ka [M-1]
CB[7] , 0.001 , adamantane , 1.2e9
beta-CD , 0.002 , ferrocene , 3.4e3
```

Some trailing prose.
TEXT;

        $rows = CsvExtractionParser::parseRows($response);

        $this->assertCount(2, $rows);
        // unit hints in [..] are stripped from the header keys
        $this->assertSame(['host', 'host conc', 'guest', 'ka'], array_keys($rows[0]));
        $this->assertSame('CB[7]', $rows[0]['host']);
        $this->assertSame('1.2e9', $rows[0]['ka']);
        $this->assertSame('ferrocene', $rows[1]['guest']);
    }

    public function testReturnsEmptyWhenNoTablePresent(): void
    {
        $this->assertSame([], CsvExtractionParser::parseRows("no table here"));
    }

    public function testPadsMissingTrailingColumns(): void
    {
        $response = "```csv\na , b , c\n1 , 2\n```";
        $rows = CsvExtractionParser::parseRows($response);
        $this->assertCount(1, $rows);
        $this->assertSame(['a' => '1', 'b' => '2', 'c' => ''], $rows[0]);
    }
}
