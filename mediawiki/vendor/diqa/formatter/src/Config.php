<?php

namespace DIQA\Formatter;

use Exception;

class Config
{

    public const RIGHT_ALIGN = 0;
    public const LEFT_ALIGN = 1;
    public const CENTER_ALIGN = 2;
    public const LEFT_AND_RIGHT_ALIGN = 3;

    public const LINE_SEPARATOR = "__LINE_SEPERATOR__";
    public const DOUBLE_LINE_SEPARATOR = "__DOUBLELINE_SEPERATOR__";
    public const EMPTY_LINE_SEPARATOR = "__EMPTY_LINE_SEPERATOR__";

    private $configuredColumnWidths;
    private $effectiveColumnWidths;
    private $alignments;
    private $options;

    private $highlights;
    private $sequencesToIgnore;
    private $leftColumnPaddings;

    /**
     * Formatting configuration.
     *
     * @param array $columnWidths array of column widths (in characters)
     * @param array|null $alignments array of column alignments
     * @param array|null $options Options
     * @throws Exception in case the configuration is inconsistent
     */
    public function __construct(array $columnWidths, array $alignments = null, array $options = null)
    {
        $this->configuredColumnWidths = $columnWidths;
        $this->alignments = $alignments;
        if (!is_null($alignments) && count($alignments) !== count($columnWidths)) {
            throw new Exception("Number of columns and alignments must match");
        }
        $this->options = is_null($options) ? [] : $options;

        $this->highlights = [];
        $this->sequencesToIgnore = [];
        $this->leftColumnPaddings = [];

        $this->recalculateColumnWidths();
    }

    /**
     * Highlights $word with $color.
     *
     * @param string $word the word to highlight with color
     * @param Color $color color to use
     * @param mixed $column column where to highlight only (optional)
     */
    public function highlightWord(string $word, Color $color, $column = NULL): Config
    {
        $this->highlights[$word] = [ 'color' => $color, 'column' => $column ];
        return $this;
    }

    /**
     * Returns words which should be highlighted with a color.
     *
     * @return array
     */
    public function getHighlights(): array
    {
        return $this->highlights;
    }

    /**
     * Defines character sequences which are ignored by layout.
     *
     * @param $sequencesToIgnore
     */
    public function setSequencesToIgnore($sequencesToIgnore): Config
    {
        $this->sequencesToIgnore = $sequencesToIgnore;
        return $this;
    }

    /**
     * Returns character sequences which are ignored by layout.
     *
     * @return array
     */
    public function getSequencesToIgnore(): array
    {
        return $this->sequencesToIgnore;
    }


    /**
     * Returns width of a column.
     *
     * @param int $index of column
     * @return int
     */
    public function getColumnWidth(int $index): int
    {
        return $this->effectiveColumnWidths[$index];
    }

    /**
     * Returns number of columns.
     *
     * @return int
     */
    public function getNumberOfColumns(): int
    {
        return count($this->effectiveColumnWidths);
    }

    /**
     * Returns alignment for a column.
     *
     * @param int $index of column
     * @return int
     */
    public function getAlignments(int $index): int
    {
        if (is_null($this->alignments)) {
            return self::LEFT_ALIGN;
        }
        return $this->alignments[$index];
    }

    public function hasBorderPadding(): bool
    {
        return $this->options['borderPadding'] ?? false;
    }

    public function hasBorder(): bool
    {
        return $this->options['border'] ?? false;
    }

    public function paddingChar(): string
    {
        return $this->options['paddingChar'] ?? ' ';
    }

    public function lineFeed(): string
    {
        return $this->options['lineFeed'] ?? false;
    }

    public function wrapColumns(): string
    {
        return $this->options['wrapColumns'] ?? true;
    }

    public function setLeftColumnPadding(int $column, int $leftPadding): Config
    {
        $this->leftColumnPaddings[$column] = $leftPadding;
        $this->recalculateColumnWidths();
        return $this;
    }

    public function getLeftColumnPadding($column): int {
        return $this->leftColumnPaddings[$column] ?? 0;
    }

    private function recalculateColumnWidths() {
        $this->effectiveColumnWidths = $this->configuredColumnWidths;
        if ($this->hasBorderPadding()) {
            for ($i = 0; $i < count($this->effectiveColumnWidths) ; $i++) {
                $this->effectiveColumnWidths[$i] -= 2;
            }
        }
        if ($this->hasBorder()) {
            $this->effectiveColumnWidths[0]--;
            for ($i = 0; $i < count($this->effectiveColumnWidths) ; $i++) {
                $this->effectiveColumnWidths[$i]--;
            }
        }
        foreach($this->leftColumnPaddings as $column => $leftPadding) {
            $this->effectiveColumnWidths[$column] -= $leftPadding;
        }
    }
}