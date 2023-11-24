<?php
namespace DIQA\Formatter;

use PHPUnit\Framework\TestCase;

final class GeneralTest extends TestCase
{
    use Tools;

    public function testFormatLine(): void
    {
        $config = new Config(
            [20, 30, 30],
            [Config::LEFT_ALIGN, Config::CENTER_ALIGN, Config::RIGHT_ALIGN],
            ['borderPadding' => false, 'border' => false]
        );

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->formatLine("row 1 column 1", "row 1 column 2", "254,00");

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
row 1 column 1              row 1 column 2                                254,00
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

    public function testLineFeed(): void
    {
        $config = new Config(
            [20, 30, 30],
            [Config::LEFT_ALIGN, Config::CENTER_ALIGN, Config::RIGHT_ALIGN],
            ['borderPadding' => false, 'border' => false, 'lineFeed' => true]
        );

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->formatLine("row 1 column 1", "row 1 column 2", "254,00");

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT

row 1 column 1              row 1 column 2                                254,00
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }
}