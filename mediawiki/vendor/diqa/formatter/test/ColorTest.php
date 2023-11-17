<?php
namespace DIQA\Formatter;

use Exception;
use PHPUnit\Framework\TestCase;

final class ColorTest extends TestCase
{
    use Tools;

    /**
     * @throws Exception
     */
    public function testHighlightWord(): void
    {
        $config = new Config(
            [20, 30, 30],
            [Config::LEFT_ALIGN, Config::CENTER_ALIGN, Config::RIGHT_ALIGN],
            ['borderPadding' => false, 'border' => false]
        );
        $greenColor = Color::fromColor(Color::GREEN);
        $config->highlightWord("OK", $greenColor);

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->format([
            ["row 1 column 1", "row 1 column 2", "[ERROR]"],
            ["row 2 column 1", "row 2 column 2", "[OK]"],
        ]);

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
row 1 column 1              row 1 column 2                               [ERROR]
row 2 column 1              row 2 column 2                                  [<OK>]
EOT;

        $expectedOutput = self::highlightWithColor($expectedOutput, "OK", $greenColor);

        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

    /**
     * @throws Exception
     */
    public function testHighlightWordWithColumn(): void
    {
        $config = new Config(
            [20, 30, 30],
            [Config::LEFT_ALIGN, Config::CENTER_ALIGN, Config::RIGHT_ALIGN],
            ['borderPadding' => false, 'border' => false]
        );
        $greenColor = Color::fromColor(Color::GREEN);
        $config->highlightWord("OK", $greenColor, 2);

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->format([
            ["row 1 column 1", "row 1 column 2", "[ERROR]"],
            ["row 2 column 1", "row 2 OK column 2", "[OK]"],
        ]);

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
row 1 column 1              row 1 column 2                               [ERROR]
row 2 column 1            row 2 OK column 2                                 [<OK>]
EOT;
        $expectedOutput = self::highlightWithColor($expectedOutput, "OK", $greenColor);

        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

    /**
     * @throws Exception
     */
    public function testHighlightWordBackground(): void
    {
        $config = new Config(
            [20, 30, 30],
            [Config::LEFT_ALIGN, Config::CENTER_ALIGN, Config::RIGHT_ALIGN],
            ['borderPadding' => false, 'border' => false]
        );
        $blackWithRedBgd = Color::fromColor(Color::LIGHT_GREY, Color::RED);
        $config->highlightWord("OK", $blackWithRedBgd);

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->format([
            ["row 1 column 1", "row 1 column 2", "[ERROR]"],
            ["row 2 column 1", "row 2 column 2", "[OK]"],
        ]);

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
row 1 column 1              row 1 column 2                               [ERROR]
row 2 column 1              row 2 column 2                                  [<OK>]
EOT;
        $expectedOutput = self::highlightWithColor($expectedOutput, "OK", $blackWithRedBgd);

        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }
}