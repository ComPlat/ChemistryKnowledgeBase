<?php

namespace DIQA\Formatter;

class Color {

    public const LIGHT_GREY	= [ "0;37", "47" ];
    public const BLACK	= [ "0;30", "40" ];
    public const RED = [ "0;31", "41" ];
    public const YELLOW = [ "1;33", "43"];
    public const GREEN = [ "0;32", "42" ];
    public const BLUE = [ "0;34", "44" ];
    public const MAGENTA = [ "0;35", "45" ];
    public const CYAN = [ "0;36", "46" ];
    public const WHITE_FOREGROUND = [ "1;37", NULL ];
    public const LIGHTGREEN_FOREGROUND = [ "1;32", NULL ];
    public const BROWN_FOREGROUND = [ "0;33", NULL ];
    public const LIGHTBLUE_FOREGROUND = [ "1;34", NULL ];
    public const DARKGREY_FOREGROUND = [ "1;30", NULL ];
    public const LIGHTRED_FOREGROUND = [ "1;31", NULL ];
    public const LIGHTCYAN_FOREGROUND = [ "1;36", NULL ];
    public const LIGHTMAGENTA_FOREGROUND = [ "1;35", NULL ];

    private $foreground;
    private $background;

    private function __construct($foreground, $background)
    {
        $this->foreground = $foreground;
        $this->background = $background;
    }

    /**
     * Creates a color instance.
     *
     * Note: Color constants ending with FOREGROUND cannot be used as background colors.
     *
     * @param array $foreground Color constants (Color::RED, Color::GREEN, Color::WHITE_FOREGROUND, ....)
     * @param array|null $background Color constants (Color::RED, Color::GREEN, ....)
     */
    public static function fromColor(array $foreground, array $background = NULL): Color
    {
        return new Color($foreground[0], !is_null($background) ? $background[1] : NULL);
    }

    /**
     * Returns color string to switch console to the given color when printed
     *
     * @return string
     */
    public function getColorString(): string
    {
        if (is_null($this->background)) {
            return  "\033[0;{$this->foreground}m";
        } else {
            return "\033[0;{$this->foreground};{$this->background}m";
        }
    }
}