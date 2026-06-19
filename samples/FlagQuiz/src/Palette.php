<?php

namespace Samples\FlagQuiz;

use BrickPHP\UI\Color;

/**
 * The Flagdle colour scheme — the design's warm-neutral + blue palette mapped
 * to BrickPHP's named ramps (stone/blue/red). Centralised here so every
 * component shares one source of truth; the CssExtractor resolves these static
 * calls (via each file's `use` import) and harvests the literals below, so the
 * named colours emit valid utility-class selectors.
 */
final class Palette
{
    public static function ink(): Color { return Color::stone(900); }
    public static function blue(): Color { return Color::blue(500); }
    public static function blueDark(): Color { return Color::blue(700); }
    public static function blueWash(): Color { return Color::blue(50); }
    public static function page(): Color { return Color::stone(100); }
    public static function offWhite(): Color { return Color::stone(50); }
    public static function border(): Color { return Color::stone(200); }
    public static function subtle(): Color { return Color::stone(500); }
    public static function labelMuted(): Color { return Color::stone(400); }
    public static function track(): Color { return Color::stone(200); }
    public static function red(): Color { return Color::red(500); }
    public static function redWash(): Color { return Color::red(50); }
    public static function green(): Color { return Color::emerald(500); }
    public static function footerCount(): Color { return Color::stone(400); }
    public static function dot(): Color { return Color::stone(300); }
    public static function white(): Color { return Color::white(); }
    public static function transparent(): Color { return Color::transparent(); }
}
