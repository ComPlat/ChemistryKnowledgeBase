<?php
namespace DIQA\Formatter;

use PHPUnit\Framework\TestCase;

final class AlignmentTest extends TestCase
{
    use Tools;

    public function testLeftRightCenterAlignments(): void
    {
        $config = new Config(
            [20, 30, 30],
            [Config::LEFT_ALIGN, Config::CENTER_ALIGN, Config::RIGHT_ALIGN],
            ['borderPadding' => false, 'border' => false]
        );

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->format([
            ["row 1 column 1", "row 1 column 2", "254,00"],
            ["row 2 column 1", "row 2 column 2", "1.233,00"],
            ["row 3 column 1", "row 3 column 2", "424,21"],
        ]);

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
row 1 column 1              row 1 column 2                                254,00
row 2 column 1              row 2 column 2                              1.233,00
row 3 column 1              row 3 column 2                                424,21
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

    public function testLeftAndRightAlignment(): void
    {
        $config = new Config(
            [20],
            [Config::LEFT_AND_RIGHT_ALIGN],
            ['borderPadding' => false, 'border' => false]
        );

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->formatLine(["left", "right"]);

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
left           right
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

    public function testLeftAndRightTooLongAlignment(): void
    {
        $config = new Config(
            [20],
            [Config::LEFT_AND_RIGHT_ALIGN],
            ['borderPadding' => false, 'border' => false]
        );

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->formatLine(
            ["left and a super long line", "right"]

        );

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
left and a super    
long line      right
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

    public function testLeftAndRightTooLongNotBreakableAlignment(): void
    {
        $config = new Config(
            [20],
            [Config::LEFT_AND_RIGHT_ALIGN],
            ['borderPadding' => false, 'border' => false]
        );

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->formatLine(
            ["Test", "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA"]

        );

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
Test                
AAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAA
      AAAAAAAAAAAAAA
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

    public function testLeftAndRightTooLongNotBreakableAlignment2(): void
    {
        $config = new Config(
            [20],
            [Config::LEFT_AND_RIGHT_ALIGN],
            ['borderPadding' => false, 'border' => false]
        );

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->formatLine(
            ["AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA", "BBBB"]

        );

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
AAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAA  BBBB
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }


    public function testLeftAndRightNoColumnWrapAlignment(): void
    {
        $config = new Config(
            [20],
            [Config::LEFT_AND_RIGHT_ALIGN],
            ['borderPadding' => false, 'border' => false, 'wrapColumns' => false]
        );

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->formatLine(
            ["Test", "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA"]

        );

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
Test         ...AAAA
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }

    public function testLeftAndRightNoColumnWrapAlignment2(): void
    {
        $config = new Config(
            [20],
            [Config::LEFT_AND_RIGHT_ALIGN],
            ['borderPadding' => false, 'border' => false, 'wrapColumns' => false]
        );

        $formatter = new Formatter($config);

        $formattedOutput = $formatter->formatLine(
            ["AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA", "BBBBB"]

        );

        print "\n$formattedOutput";

        $expectedOutput = <<<EOT
AAAAAAAA...    BBBBB
EOT;
        $this->assertEquals(
            self::normalize($expectedOutput),
            self::normalize($formattedOutput)
        );
    }


}