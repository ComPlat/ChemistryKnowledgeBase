<?php

namespace DIQA\FacetedSearch2\Utils;

final class ConfusableCharacterNormalizer
{
    /**
     * Map of "visually identical" / confusable characters to their canonical ASCII equivalents.
     * Extend this list as needed for your domain.
     */
    private const CONFUSABLES = [
        // --- Whitespace variants ---
            "\u{00A0}" => ' ',  // NO-BREAK SPACE
            "\u{1680}" => ' ',  // OGHAM SPACE MARK
            "\u{2000}" => ' ',  // EN QUAD
            "\u{2001}" => ' ',  // EM QUAD
            "\u{2002}" => ' ',  // EN SPACE
            "\u{2003}" => ' ',  // EM SPACE
            "\u{2004}" => ' ',  // THREE-PER-EM SPACE
            "\u{2005}" => ' ',  // FOUR-PER-EM SPACE
            "\u{2006}" => ' ',  // SIX-PER-EM SPACE
            "\u{2007}" => ' ',  // FIGURE SPACE
            "\u{2008}" => ' ',  // PUNCTUATION SPACE
            "\u{2009}" => ' ',  // THIN SPACE
            "\u{200A}" => ' ',  // HAIR SPACE
            "\u{202F}" => ' ',  // NARROW NO-BREAK SPACE
            "\u{205F}" => ' ',  // MEDIUM MATHEMATICAL SPACE
            "\u{3000}" => ' ',  // IDEOGRAPHIC SPACE

        // --- Zero-width / invisible characters (stripped) ---
            "\u{200B}" => '',   // ZERO WIDTH SPACE
            "\u{200C}" => '',   // ZERO WIDTH NON-JOINER
            "\u{200D}" => '',   // ZERO WIDTH JOINER
            "\u{2060}" => '',   // WORD JOINER
            "\u{FEFF}" => '',   // ZERO WIDTH NO-BREAK SPACE / BOM

        // --- Hyphens / dashes ---
            "\u{2010}" => '-',  // HYPHEN
            "\u{2011}" => '-',  // NON-BREAKING HYPHEN
            "\u{2012}" => '-',  // FIGURE DASH
            "\u{2013}" => '-',  // EN DASH
            "\u{2014}" => '-',  // EM DASH
            "\u{2015}" => '-',  // HORIZONTAL BAR
            "\u{2212}" => '-',  // MINUS SIGN
            "\u{FE58}" => '-',  // SMALL EM DASH
            "\u{FE63}" => '-',  // SMALL HYPHEN-MINUS
            "\u{FF0D}" => '-',  // FULLWIDTH HYPHEN-MINUS

        // --- Single quotes / apostrophes ---
            "\u{2018}" => "'",  // LEFT SINGLE QUOTATION MARK
            "\u{2019}" => "'",  // RIGHT SINGLE QUOTATION MARK
            "\u{201A}" => "'",  // SINGLE LOW-9 QUOTATION MARK
            "\u{201B}" => "'",  // SINGLE HIGH-REVERSED-9 QUOTATION MARK
            "\u{2032}" => "'",  // PRIME
            "\u{00B4}" => "'",  // ACUTE ACCENT
            "\u{02BC}" => "'",  // MODIFIER LETTER APOSTROPHE

        // --- Double quotes ---
            "\u{201C}" => '"',  // LEFT DOUBLE QUOTATION MARK
            "\u{201D}" => '"',  // RIGHT DOUBLE QUOTATION MARK
            "\u{201E}" => '"',  // DOUBLE LOW-9 QUOTATION MARK
            "\u{201F}" => '"',  // DOUBLE HIGH-REVERSED-9 QUOTATION MARK
            "\u{2033}" => '"',  // DOUBLE PRIME
            "\u{00AB}" => '"',  // LEFT-POINTING DOUBLE ANGLE QUOTATION MARK
            "\u{00BB}" => '"',  // RIGHT-POINTING DOUBLE ANGLE QUOTATION MARK

        // --- Ellipsis ---
            "\u{2026}" => '...',

        // --- Bullets / middle dots ---
            "\u{00B7}" => '.',  // MIDDLE DOT
            "\u{2022}" => '*',  // BULLET
            "\u{2027}" => '.',  // HYPHENATION POINT

        // --- Ligatures often produced by PDF extraction ---
            "\u{FB00}" => 'ff',
            "\u{FB01}" => 'fi',
            "\u{FB02}" => 'fl',
            "\u{FB03}" => 'ffi',
            "\u{FB04}" => 'ffl',
            "\u{FB05}" => 'st',
            "\u{FB06}" => 'st',

        // --- Cyrillic look-alikes of Latin letters ---
            "\u{0410}" => 'A', "\u{0412}" => 'B', "\u{0415}" => 'E',
            "\u{041A}" => 'K', "\u{041C}" => 'M', "\u{041D}" => 'H',
            "\u{041E}" => 'O', "\u{0420}" => 'P', "\u{0421}" => 'C',
            "\u{0422}" => 'T', "\u{0425}" => 'X',
            "\u{0430}" => 'a', "\u{0435}" => 'e', "\u{043E}" => 'o',
            "\u{0440}" => 'p', "\u{0441}" => 'c', "\u{0445}" => 'x',
            "\u{0443}" => 'y',

        // --- Greek look-alikes ---
            "\u{0391}" => 'A', "\u{0392}" => 'B', "\u{0395}" => 'E',
            "\u{0396}" => 'Z', "\u{0397}" => 'H', "\u{0399}" => 'I',
            "\u{039A}" => 'K', "\u{039C}" => 'M', "\u{039D}" => 'N',
            "\u{039F}" => 'O', "\u{03A1}" => 'P', "\u{03A4}" => 'T',
            "\u{03A7}" => 'X', "\u{03A5}" => 'Y',
    ];

    /**
     * Normalizes a string by:
     *   1. Applying Unicode NFC normalization (combines decomposed sequences).
     *   2. Replacing common confusable characters with their canonical ASCII equivalents.
     *
     * @param string $text The input string (expected to be UTF-8).
     * @return string The normalized string.
     */
    public static function normalize(string $text): string
    {
        // Step 1: Unicode normalization (NFC) so that e.g. "e" + combining acute
        // becomes the single code point "é" before we look up replacements.
        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($text, \Normalizer::FORM_C);
            if ($normalized !== false) {
                $text = $normalized;
            }
        }

        // Step 2: Replace confusable characters.
        return strtr($text, self::CONFUSABLES);
    }
}