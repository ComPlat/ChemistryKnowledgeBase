<?php

namespace DIQA\Formatter;

use Exception;

class Formatter
{
    private const SINGLE_LINE = "\u{2500}";
    private const DOUBLE_LINE = "\u{2550}";
    private const EMPTY_LINE = " ";
    private const PIPE = "\u{2502}";
    private const NC = "\033[0m"; # No Color
    private const GOLDEN_RATIO = 0.618;

    private $config;
    private $textUtilities;

    /**
     * Creates a formatter object with the given configuration.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->textUtilities = new TextUtilities($config);
    }

    /**
     * @throws Exception
     */
    public function formatLine(...$columns): string
    {
        return $this->format([$columns]);
    }

    /**
     * Formats text according to set configuration.
     *
     * @param array $rows Rows to print as 2-dim array (rows with columns)
     * @return string
     * @throws Exception
     */
    public function format(array $rows): string
    {
        $resultLines = [];

        for ($j = 0; $j < count($rows); $j++) {

            if ($rows[$j] === Config::LINE_SEPARATOR
                || $rows[$j] === Config::DOUBLE_LINE_SEPARATOR
                || $rows[$j] === Config::EMPTY_LINE_SEPARATOR) {
                $columns = [];
                for ($i = 0; $i < $this->config->getNumberOfColumns(); $i++) {
                    $columns[] = $rows[$j];
                }
                $rows[$j] = $columns;
            }

            if ($this->config->hasBorder()) {
                $resultLines[] = $this->formatBorder($j, count($rows));
            }

            $this->formatOneLine($rows[$j], $resultLines);
        }
        if ($this->config->hasBorder()) {
            $resultLines[] = $this->formatBorder(count($rows), count($rows));
        }

        $prefix = "";
        if ($this->config->lineFeed()) {
            $prefix = "\n";
        }
        return $prefix . implode("\n", $resultLines);
    }

    /**
     * Renders aligned text for a column.
     *
     * @param mixed $columnValue Lines a single row was split into
     * @param int $column The column
     * @return string
     * @throws Exception
     */
    private function alignColumn($columnValue, int $column): string
    {
        $columnWidth = $this->config->getColumnWidth($column);
        $leftPadding = str_repeat($this->config->paddingChar(), $this->config->getLeftColumnPadding($column));
        switch ($this->config->getAlignments($column)) {
            case Config::LEFT_ALIGN:
            default:
                $this->checkColumnInput($columnValue);
                $columnLine = $leftPadding . $this->textUtilities->rightPad($columnValue, $columnWidth);
                break;
            case Config::RIGHT_ALIGN:
                $this->checkColumnInput($columnValue);
                $columnLine = $this->textUtilities->leftPad($columnValue, $columnWidth);
                break;
            case Config::CENTER_ALIGN:
                $this->checkColumnInput($columnValue);
                $columnLine = $this->textUtilities->centerPad($columnValue, $columnWidth);
                break;
            case Config::LEFT_AND_RIGHT_ALIGN:
                // if line consists of left and right part do left and right alignment
                // otherwise just do left alignment
                if (is_array($columnValue)) {
                    $columnLine = $leftPadding . $this->textUtilities->leftAndRightPad($columnValue[0], $columnValue[1], $columnWidth);
                } else {
                    $columnLine = $leftPadding . $this->textUtilities->rightPad($columnValue, $columnWidth);
                }
                break;
        }
        return $columnLine;
    }

    /**
     * Breaks a long text for a column in several short ones that fit the column in multiple lines.
     *
     * @param array $row columns of a row
     * @return array $linesOfRow = 2-dim array of columns and lines per column. usually columns do not have same
     * amount of lines
     *
     * @throws Exception
     */
    private function wrapLinesIfNecessary(array $row): array
    {
        $linesOfRow = [];
        for ($c = 0; $c < count($row); $c++) {
            if ($row[$c] === Config::LINE_SEPARATOR
                || $row[$c] === Config::DOUBLE_LINE_SEPARATOR
                || $row[$c] === Config::EMPTY_LINE_SEPARATOR) {
                $linesOfRow[] = [ $this->formatSeparator($row[$c], $this->config->getColumnWidth($c)) ];
                continue;
            }
            $columnWidth = $this->config->getColumnWidth($c);
            if ($this->config->getAlignments($c) === Config::LEFT_AND_RIGHT_ALIGN) {
                if (!is_array($row[$c]) || count($row[$c]) !== 2) {
                    throw new Exception("expect an array of size 2 as content of column $c when using left-and-right-alignment");
                }
                $linesOfRow = $this->wrapLeftAndRightAlignment($row[$c], $columnWidth, $linesOfRow);
            } else {
                $linesOfRow = $this->wrapOtherAlignments($row[$c], $columnWidth, $linesOfRow);
            }
        }

        return $linesOfRow;
    }

    /**
     * Wraps left- and right-aligned columns.
     *
     * @param array $column Column
     * @param int $columnWidth Column width
     * @param array $linesOfRow = 2-dim array of columns and lines per column. usually columns do not have same
     * amount of lines
     * @return array
     */
    private function wrapLeftAndRightAlignment(array $column, int $columnWidth, array $linesOfRow): array
    {
        $leftPart = $column[0];
        $rightPart = $column[1];
        // column consists of left and right part used for left-right alignment
        // if too long, treat it as normal line
        $bothColumnsWithoutIgnored = str_replace($this->config->getSequencesToIgnore(), '', "$leftPart $rightPart");
        if ($columnWidth < mb_strlen($bothColumnsWithoutIgnored)) {
            if ($this->config->wrapColumns()) {
                $wrappedLines = $this->textUtilities->breakText(trim("$leftPart"), $columnWidth);
                $lines = [];
                for ($i = 0; $i < count($wrappedLines) - 1; $i++) {
                    $lines[] = [$wrappedLines[$i], ''];
                }
                $lastLine = $wrappedLines[count($wrappedLines) - 1];
                if ($columnWidth >= mb_strlen("$lastLine $rightPart")) {
                    $lines[] = [$lastLine, $rightPart];
                } else {
                    $lines[] = [$lastLine, ''];
                    $wrappedLines = $this->textUtilities->breakText(trim("$rightPart"), $columnWidth);
                    foreach ($wrappedLines as $line) {
                        $lines[] = ['', $line];
                    }
                }
                $linesOfRow[] = $lines;
            } else {
                $left = $this->textUtilities->shortenRight($leftPart, floor($columnWidth * self::GOLDEN_RATIO) - 1);
                $right = $this->textUtilities->shortenLeft($rightPart, floor($columnWidth * (1 - self::GOLDEN_RATIO)));
                $linesOfRow[] = [[$left, $right]];
            }
        } else {
            $linesOfRow[] = [$column];
        }
        return $linesOfRow;
    }

    /**
     * Wrap left-, right- and center-aligned columns
     *
     * @param string $column content of column
     * @param int $columnWidth Column width
     * @param array $linesOfRow = 2-dim array of columns and lines per column. usually columns do not have same
     * amount of lines
     * @return array
     */
    private function wrapOtherAlignments($column, int $columnWidth, array $linesOfRow): array
    {
        if ($this->config->wrapColumns()) {
            $linesOfRow[] = $this->textUtilities->breakText(trim($column), $columnWidth);
        } else {
            $linesOfRow[] = [$this->textUtilities->shortenRight(trim($column), $columnWidth)];
        }
        return $linesOfRow;
    }

    /**
     * Add a separator line if necessary.
     *
     * @param string $separatorType
     * @param int $length Length of column
     * @return string
     */
    private function formatSeparator(string $separatorType, int $length): string
    {
        $separator = '';
        if ($separatorType === Config::LINE_SEPARATOR) {
            $separator = self::SINGLE_LINE;
        } else if ($separatorType === Config::DOUBLE_LINE_SEPARATOR) {
            $separator = self::DOUBLE_LINE;
        } else if ($separatorType === Config::EMPTY_LINE_SEPARATOR) {
            $separator = self::EMPTY_LINE;
        }
        return str_repeat($separator, $length);
    }

    /**
     * Highlight configured substrings with a color
     *
     * @param string $s The string with substrings to highlight
     * @return string the string with color highlights
     */
    private function highlightIfNecessary(string $s, int $column): string
    {
        foreach ($this->config->getHighlights() as $word => $colorDescriptor) {
            $color = $colorDescriptor['color']->getColorString();
            if (is_null($colorDescriptor['column']) || $colorDescriptor['column'] === $column) {
                $s = str_replace($word, "$color$word" . self::NC, $s);
            }
        }
        return $s;
    }

    /**
     * Renders a border separator line.
     *
     * @param int $row current row
     * @param int $totalNumberOfRows total number of rows
     * @return string
     */
    private function formatBorder(int $row, int $totalNumberOfRows): string
    {
        $line = '';
        $numberOfColumns = $this->config->getNumberOfColumns();
        $paddingCorrection = $this->config->hasBorderPadding() ? 2 : 0;

        if ($row === 0) {
            $line .= "\u{250C}";
            for ($c = 0; $c < $numberOfColumns - 1; $c++) {
                $line .= str_repeat(self::SINGLE_LINE, $this->config->getColumnWidth($c) + $paddingCorrection);
                $line .= "\u{252C}";
            }
            $line .= str_repeat(self::SINGLE_LINE, $this->config->getColumnWidth($numberOfColumns - 1) + $paddingCorrection);
            $line .= "\u{2510}";

        } else if ($row < $totalNumberOfRows) {
            $line .= "\u{251C}";
            for ($c = 0; $c < $numberOfColumns - 1; $c++) {
                $line .= str_repeat(self::SINGLE_LINE, $this->config->getColumnWidth($c) + $paddingCorrection);
                $line .= "\u{253C}";
            }
            $line .= str_repeat(self::SINGLE_LINE, $this->config->getColumnWidth($numberOfColumns - 1) + $paddingCorrection);
            $line .= "\u{2524}";
        } else if ($row === $totalNumberOfRows) {
            $line .= "\u{2514}";
            for ($c = 0; $c < $numberOfColumns - 1; $c++) {
                $line .= str_repeat(self::SINGLE_LINE, $this->config->getColumnWidth($c) + $paddingCorrection);
                $line .= "\u{2534}";;
            }
            $line .= str_repeat(self::SINGLE_LINE, $this->config->getColumnWidth($numberOfColumns - 1) + $paddingCorrection);
            $line .= "\u{2518}";;
        }
        return $line;
    }

    /**
     * Formats one line of the input.
     *
     * @param array $inputLine Columns of the line
     * @param array $resultLines output lines
     * @throws Exception
     */
    private function formatOneLine(array $inputLine, array &$resultLines)
    {
        $wrappedInputLines = $this->wrapLinesIfNecessary($inputLine);

        // get maximum number of lines of all columns
        $sizes = array_map(function ($e) {
            return count($e);
        }, $wrappedInputLines);
        $maxLines = max($sizes);

        for ($i = 0; $i < $maxLines; $i++) {
            $currentLine = '';
            for ($c = 0; $c < count($wrappedInputLines); $c++) {
                if ($this->config->hasBorder() && $c < $this->config->getNumberOfColumns()) {
                    $currentLine .= self::PIPE;
                }

                $text = $wrappedInputLines[$c][$i] ?? '';
                $columnLine = $this->alignColumn($text, $c);

                $columnLine = $this->highlightIfNecessary($columnLine, $c);
                $currentLine .= $this->config->hasBorderPadding() ? $this->config->paddingChar() : '';
                $currentLine .= $columnLine;
                $currentLine .= $this->config->hasBorderPadding() ? $this->config->paddingChar() : '';

            }
            if ($this->config->hasBorder()) {
                $currentLine .= self::PIPE;
            }
            $resultLines[] = $currentLine;
        }

    }

    /**
     * @param $columnValue
     * @throws Exception
     */
    private function checkColumnInput($columnValue): void
    {
        if (is_array($columnValue)) {
            throw new Exception("Detected array where should be string. Wrong alignment used?");
        }
    }

}