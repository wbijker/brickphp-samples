<?php

namespace Samples\Docs\Components;

use BrickPHP\UI\Color;

/**
 * Atom's "One Dark" palette as a strongly typed {@see SyntaxTheme}.
 * Every value is set by passing a Color to the parent constructor — no
 * static lookup tables, no implicit defaults.
 *
 *   #282c34 background  → slate-800
 *   #abb2bf default fg  → slate-300
 *   #c678dd keyword     → purple-400
 *   #98c379 string      → lime-400
 *   #d19a66 number      → orange-300
 *   #e06c75 variable    → red-400
 *   #61afef function    → blue-400
 *   #e5c07b class       → yellow-300
 *   #56b6c2 operator    → cyan-400
 *   #5c6370 comment     → slate-500
 */
class AtomOneDark extends SyntaxTheme
{
    public function __construct()
    {
        parent::__construct(
            background:   Color::slate(800),
            defaultColor: Color::slate(300),
            comment:      Color::slate(500),
            string:       Color::lime(400),
            number:       Color::orange(300),
            variable:     Color::red(400),
            keyword:      Color::purple(400),
            tag:          Color::purple(400),
            constant:     Color::orange(300),
            functionName: Color::blue(400),
            className:    Color::yellow(300),
            operator:     Color::cyan(400),
        );
    }
}
