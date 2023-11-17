<?php
namespace DIQA\Formatter;

use PHPUnit\Framework\TestCase;

final class AutomaticLineBreakTest extends TestCase
{
    use Tools;

    public function testAutomaticLineBreak(): void
    {
        $config = new Config(
            [20, 30, 30],
            [Config::LEFT_ALIGN, Config::CENTER_ALIGN, Config::RIGHT_ALIGN],
            ['borderPadding' => false, 'border' => false]
        );

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->format([
            ["row 1 column 1", "row 1 column 2", "254,00"],
            ["row 2 column 1", "das ist viel Text für eine kleine Spalte", "254,00"],
            ["row 3 column 1", "row 3 column 2", "424,21"],
        ]);

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
row 1 column 1              row 1 column 2                                254,00
row 2 column 1        das ist viel Text für eine                          254,00
                            kleine Spalte                                       
row 3 column 1              row 3 column 2                                424,21
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

    public function testColumnWrapOff(): void
    {
        $config = new Config(
            [20, 30, 30],
            [Config::LEFT_ALIGN, Config::CENTER_ALIGN, Config::RIGHT_ALIGN],
            ['borderPadding' => false, 'border' => false, 'wrapColumns' => false]
        );

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->format([
            ["row 1 column 1", "row 1 column 2", "254,00"],
            ["row 2 column 1", "das ist viel Text für eine kleine Spalte", "254,00"],
            ["row 3 column 1", "row 3 column 2", "424,21"],
        ]);

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
row 1 column 1              row 1 column 2                                254,00
row 2 column 1      das ist viel Text für eine ...                        254,00
row 3 column 1              row 3 column 2                                424,21
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

}