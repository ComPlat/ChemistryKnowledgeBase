<?php
namespace DIQA\Formatter;

use PHPUnit\Framework\TestCase;

final class BorderAndPaddingTest extends TestCase
{
    use Tools;

    public function test3ColumnsWithBorderAndPadding(): void
    {
        $config = new Config(
            [20, 30, 30],
            [Config::LEFT_ALIGN, Config::LEFT_ALIGN, Config::RIGHT_ALIGN],
            ['borderPadding' => true, 'border' => true]
        );

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->format([
            ["row 1 column 1", "row 1 column 2", "254,00"],
            ["row 2 column 1", "row 2 column 2", "1.233,00"],
            ["row 3 column 1", "row 3 column 2 ", "424,21"],
        ]);

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
┌──────────────────┬─────────────────────────────┬─────────────────────────────┐
│ row 1 column 1   │ row 1 column 2              │                      254,00 │
├──────────────────┼─────────────────────────────┼─────────────────────────────┤
│ row 2 column 1   │ row 2 column 2              │                    1.233,00 │
├──────────────────┼─────────────────────────────┼─────────────────────────────┤
│ row 3 column 1   │ row 3 column 2              │                      424,21 │
└──────────────────┴─────────────────────────────┴─────────────────────────────┘
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

    public function test3ColumnsWithBorder(): void
    {
        $config = new Config(
            [20, 30, 30],
            [Config::LEFT_ALIGN, Config::LEFT_ALIGN, Config::RIGHT_ALIGN],
            ['borderPadding' => false, 'border' => true]
        );

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->format([
            ["row 1 column 1", "row 1 column 2", "254,00"],
            ["row 2 column 1", "row 2 column 2", "1.233,00"],
            ["row 3 column 1", "row 3 column 2 ", "424,21"],
        ]);

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
┌──────────────────┬─────────────────────────────┬─────────────────────────────┐
│row 1 column 1    │row 1 column 2               │                       254,00│
├──────────────────┼─────────────────────────────┼─────────────────────────────┤
│row 2 column 1    │row 2 column 2               │                     1.233,00│
├──────────────────┼─────────────────────────────┼─────────────────────────────┤
│row 3 column 1    │row 3 column 2               │                       424,21│
└──────────────────┴─────────────────────────────┴─────────────────────────────┘
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

    public function test3Columns(): void
    {
        $config = new Config(
            [20, 30, 30],
            [Config::LEFT_ALIGN, Config::LEFT_ALIGN, Config::RIGHT_ALIGN],
            ['borderPadding' => false, 'border' => false]
        );

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->format([
            ["row 1 column 1", "row 1 column 2", "254,00"],
            ["row 2 column 1", "row 2 column 2", "1.233,00"],
            ["row 3 column 1", "row 3 column 2 ", "424,21"],
        ]);

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
row 1 column 1      row 1 column 2                                        254,00
row 2 column 1      row 2 column 2                                      1.233,00
row 3 column 1      row 3 column 2                                        424,21
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

    public function test3ColumnsWithPadding(): void
    {
        $config = new Config(
            [20, 30, 30],
            [Config::LEFT_ALIGN, Config::LEFT_ALIGN, Config::RIGHT_ALIGN],
            ['borderPadding' => true, 'border' => false]
        );

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->format([
            ["row 1 column 1", "row 1 column 2", "254,00"],
            ["row 2 column 1", "row 2 column 2", "1.233,00"],
            ["row 3 column 1", "row 3 column 2 ", "424,21"],
        ]);

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
 row 1 column 1      row 1 column 2                                      254,00 
 row 2 column 1      row 2 column 2                                    1.233,00 
 row 3 column 1      row 3 column 2                                      424,21 
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

    public function test3ColumnsWithPaddingChar(): void
    {
        $config = new Config(
            [20, 30, 30],
            [Config::LEFT_ALIGN, Config::LEFT_ALIGN, Config::RIGHT_ALIGN],
            ['borderPadding' => false, 'border' => false, 'paddingChar' => '.']
        );

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->format([
            ["row 1 column 1", "row 1 column 2", "254,00"],
            ["row 2 column 1", "row 2 column 2", "1.233,00"],
            ["row 3 column 1", "row 3 column 2 ", "424,21"],
        ]);

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
row 1 column 1......row 1 column 2........................................254,00
row 2 column 1......row 2 column 2......................................1.233,00
row 3 column 1......row 3 column 2........................................424,21
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

    public function test1ColumnsLeftRightAlignWithPaddingChar(): void
    {
        $config = new Config(
            [40],
            [Config::LEFT_AND_RIGHT_ALIGN],
            ['borderPadding' => false, 'border' => false, 'paddingChar' => '.']
        );

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->format([
            [['Date','2020-07-12']],
            [['Shop','Aldi Süd']],
            [['TSE-Signature','47364736473647364374837483748378437856386453']],
        ]);

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
Date..........................2020-07-12
Shop............................Aldi Süd
TSE-Signature...........................
4736473647364736437483748374837843785638
....................................6453
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

    public function testDoubleLineWithBorder(): void
    {
        $config = new Config(
            [20, 30, 30],
            [Config::LEFT_ALIGN, Config::LEFT_ALIGN, Config::RIGHT_ALIGN],
            ['border' => true]
        );

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->format([
            ["row 1 column 1", "row 1 column 2", "254,00"],
            Config::DOUBLE_LINE_SEPARATOR,
            ["row 3 column 1", "row 3 column 2 ", "424,21"],
        ]);

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
┌──────────────────┬─────────────────────────────┬─────────────────────────────┐
│row 1 column 1    │row 1 column 2               │                       254,00│
├──────────────────┼─────────────────────────────┼─────────────────────────────┤
│══════════════════│═════════════════════════════│═════════════════════════════│
├──────────────────┼─────────────────────────────┼─────────────────────────────┤
│row 3 column 1    │row 3 column 2               │                       424,21│
└──────────────────┴─────────────────────────────┴─────────────────────────────┘
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

    public function testEmptyLineWithBorder(): void
    {
        $config = new Config(
            [20, 30, 30],
            [Config::LEFT_ALIGN, Config::LEFT_ALIGN, Config::RIGHT_ALIGN],
            ['border' => true]
        );

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->format([
            ["row 1 column 1", "row 1 column 2", "254,00"],
            Config::EMPTY_LINE_SEPARATOR,
            ["row 3 column 1", "row 3 column 2 ", "424,21"],
        ]);

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
┌──────────────────┬─────────────────────────────┬─────────────────────────────┐
│row 1 column 1    │row 1 column 2               │                       254,00│
├──────────────────┼─────────────────────────────┼─────────────────────────────┤
│                  │                             │                             │
├──────────────────┼─────────────────────────────┼─────────────────────────────┤
│row 3 column 1    │row 3 column 2               │                       424,21│
└──────────────────┴─────────────────────────────┴─────────────────────────────┘
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

    public function testLeftColumnPadding(): void
    {
        $config = new Config(
            [20, 30, 30],
            [Config::LEFT_ALIGN, Config::LEFT_ALIGN, Config::RIGHT_ALIGN],
            ['borderPadding' => false, 'border' => false]
        );
        $config->setLeftColumnPadding(0, 2);

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->format([
            ["row 1 column 1", "row 1 column 2", "254,00"],
            ["row 2 column 1", "row 2 column 2", "1.233,00"],
            ["row 3 column 1", "row 3 column 2 ", "424,21"],
        ]);

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
  row 1 column 1    row 1 column 2                                        254,00
  row 2 column 1    row 2 column 2                                      1.233,00
  row 3 column 1    row 3 column 2                                        424,21
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }
}