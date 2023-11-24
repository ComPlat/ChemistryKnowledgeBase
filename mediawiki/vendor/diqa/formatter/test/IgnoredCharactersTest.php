<?php
namespace DIQA\Formatter;

use PHPUnit\Framework\TestCase;

final class IgnoredCharactersTest extends TestCase
{
    use Tools;

    public function testIgnoreFormattingCommands(): void
    {
        $config = new Config(
            [20, 30, 30],
            [Config::LEFT_ALIGN, Config::CENTER_ALIGN, Config::RIGHT_ALIGN],
            ['borderPadding' => false, 'border' => false]
        );
        $config->setSequencesToIgnore([ "//BOLD", "//ITALIC", "//OFF" ]);

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->format([
            ["//ITALICrow 1 column 1//OFF", "//BOLDrow 1 column 2//OFF", "254,00"],
            ["row 2 column 1", "row 2 column 2", "1.233,00"],
            ["row 3 column 1", "row 3 column 2", "//BOLD424,21//OFF"],
        ]);

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
//ITALICrow 1 column 1//OFF              //BOLDrow 1 column 2//OFF                                254,00
row 2 column 1              row 2 column 2                              1.233,00
row 3 column 1              row 3 column 2                                //BOLD424,21//OFF
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

    public function testIgnoreFormattingCommandsWithShortening(): void
    {
        $config = new Config(
            [30],
            [Config::LEFT_ALIGN],
            ['borderPadding' => false, 'border' => false, 'wrapColumns' => false]
        );
        $config->setSequencesToIgnore([ "//BOLD", "//ITALIC", "//OFF" ]);

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->format([
            ["//BOLDabcdefghijklmnopqrstuvwxyz0123456789//OFF"],

        ]);

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
//BOLDabcdefghijklmnopqrstuvwxyz0//OFF...
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

    public function testIgnoreFormattingCommandsWithoutShortening(): void
    {
        $config = new Config(
            [30],
            [Config::LEFT_ALIGN],
            ['borderPadding' => false, 'border' => false, 'wrapColumns' => false]
        );
        $config->setSequencesToIgnore([ "//BOLD", "//ITALIC", "//OFF" ]);

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->format([
            ["//BOLDabcdefghijklmnopqrstuvwxyz//OFF"],

        ]);

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
//BOLDabcdefghijklmnopqrstuvwxyz//OFF    
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

}