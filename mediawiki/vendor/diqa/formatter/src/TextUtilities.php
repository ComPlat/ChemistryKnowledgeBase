<?php

namespace DIQA\Formatter;

class TextUtilities
{

    private $config;
    private $ignoreReversed;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->ignoreReversed = array_map(function ($e) { return strrev($e); }, $this->config->getSequencesToIgnore());
    }

    /**
     * Breaks text in multiple lines of $maxLength. Tries to preserve words.
     *
     * @param string $line Text
     * @param int $maxLength maximum length of line
     * @return array
     */
    public function breakText(string $line, int $maxLength): array
    {

        $rows = [];
        $tokens = $this->splitTokens($line, $maxLength);

        $line = '';
        $token = reset($tokens);
        do {
            if (mb_strlen(str_replace($this->config->getSequencesToIgnore(), '', $line . $token)) >= $maxLength) {
                if ($line != '') $rows[] = $line;
                $line = $token;
            } else {
                $line .= $line == '' ? $token : ' ' . $token;
            }
        } while (($token = next($tokens)) !== false);
        if ($line != '') {
            $rows[] = $line;
        }

        return $rows;
    }

    /**
     * Shortens the right side of given text to the given length.
     *
     * @param $text
     * @param $length
     * @return string
     */
    public function shortenRight($text, $length): string
    {
        if (mb_strlen(str_replace($this->config->getSequencesToIgnore(), '', $text)) <= $length) {
            return $text;
        }
        if (count($this->config->getSequencesToIgnore()) === 0 && $length > 3) {
            return mb_substr($text, 0, $length - 3) . "...";
        }
        $ignorePatternsEscaped = array_map(function ($e) { return preg_quote($e, "/"); },
            $this->config->getSequencesToIgnore());
        $pattern = "/" . implode("|", $ignorePatternsEscaped) . "/";
        if ($length <= 3) {
            $text = preg_replace($pattern, "", $text);
            return mb_substr($text, 0, $length);
        }

        preg_match_all($pattern, $text, $matches);
        $count = 0;
        $result = '';
        $textWithoutIgnored = preg_replace($pattern, "\u{0000}", $text);
        for ($i = 0; $i < mb_strlen($textWithoutIgnored); $i++) {
            if ($count < $length - 3 || $textWithoutIgnored[$i] === "\u{0000}") {
                $result .= $textWithoutIgnored[$i];
            }
            if ($textWithoutIgnored[$i] != "\u{0000}") $count++;
        }
        foreach ($matches[0] as $m) {
            $result = $this->replaceFirst($result, "\u{0000}", $m);
        }
        return "$result...";
    }

    /**
     * Shortens the left side of given text to the given length.
     *
     * @param $text
     * @param $length
     * @return string
     */
    public function shortenLeft($text, $length): string
    {
        return strrev($this->shortenRight(strrev($text), $length, $this->ignoreReversed));
    }

    /**
     * Replaces the _first_ occurence of $needle with $replace in the $haystack.
     *
     * @param $haystack
     * @param $needle
     * @param $replace
     * @return array|mixed|string|string[]
     */
    private function replaceFirst($haystack, $needle, $replace)
    {
        $pos = strpos($haystack, $needle);
        if ($pos !== false) {
            return substr_replace($haystack, $replace, $pos, strlen($needle));
        }
        return $haystack;
    }

    /**
     * Pad text from left side to $length chars.
     *
     * @param $text
     * @param $length
     * @param string $paddingChar
     * @return string
     */
    public function leftPad($text, $length): string
    {
        return str_repeat($this->config->paddingChar(), $length -
                mb_strlen(str_replace($this->config->getSequencesToIgnore(), '', $text))) . $text;
    }

    /**
     * Pad text to right side to $length chars.
     *
     * @param $text
     * @param $length
     * @param string $paddingChar
     * @return string
     */
    public function rightPad($text, $length): string
    {
        return $text . str_repeat($this->config->paddingChar(), $length -
                mb_strlen(str_replace($this->config->getSequencesToIgnore(), '', $text)));
    }

    /**
     * Pads text from both sides to $length chars.
     *
     * @param $text
     * @param $length
     * @param string $paddingChar
     * @return string
     */
    public function centerPad($text, $length): string
    {
        $padSize = $length - mb_strlen(str_replace($this->config->getSequencesToIgnore(), '', $text));
        $leftSize = $padSize % 2 === 0 ? $padSize / 2 : ($padSize - 1) / 2;
        $rightSize = $padSize % 2 === 0 ? $padSize / 2 : ($padSize + 1) / 2;
        return str_repeat($this->config->paddingChar(), $leftSize) . $text . str_repeat(' ', $rightSize);
    }

    /**
     * Pads text in the middle to $length chars.
     *
     * @param $leftText
     * @param $rightText
     * @param $columnWidth
     * @param string $paddingChar
     * @return string
     */
    public function leftAndRightPad($leftText, $rightText, $columnWidth): string
    {
        $repeatTimes = $columnWidth - mb_strlen(str_replace($this->config->getSequencesToIgnore(), '', $leftText))
            - mb_strlen(str_replace($this->config->getSequencesToIgnore(), '', $rightText));
        return $leftText . str_repeat($this->config->paddingChar(), $repeatTimes) . $rightText;
    }

    /**
     * Splits $line in tokens delimited by space with a maximum length of $maxLength
     *
     * @param string $line Text
     * @param int $maxLength maximum length of line
     * @return array
     */
    private function splitTokens(string $line, int $maxLength): array
    {
        $tokens = explode(" ", $line);
        $newTokens = [];
        foreach ($tokens as $token) {
            if (mb_strlen($token) > $maxLength) {
                $tokensWithMaxLength = str_split($token, $maxLength);
                $newTokens = array_merge($newTokens, $tokensWithMaxLength);
            } else {
                $newTokens[] = $token;
            }
        }
        return $newTokens;
    }
}