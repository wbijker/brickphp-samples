<?php

namespace Samples\Docs\Components;

use BrickPHP\UI\Color;

/**
 * A strongly typed colour palette for a PHPCode syntax highlight scheme.
 * Every token kind PHPCode recognises has a dedicated Color slot, set
 * once via the constructor. Subclasses (e.g. {@see AtomOneDark}) preset
 * the values for a named theme by calling `parent::__construct(...)`.
 *
 * Recognised kinds for {@see colorFor()}:
 *   comment  | string   | number   | variable | keyword  | tag
 *   constant | function | class    | operator | default  (fallback)
 */
class SyntaxTheme
{
    public function __construct(
        public readonly Color $background,
        public readonly Color $defaultColor,
        public readonly Color $comment,
        public readonly Color $string,
        public readonly Color $number,
        public readonly Color $variable,
        public readonly Color $keyword,
        public readonly Color $tag,
        public readonly Color $constant,
        public readonly Color $functionName,
        public readonly Color $className,
        public readonly Color $operator,
    ) {}

    /**
     * Look up the Color for a token kind. `function` and `class` are
     * recognised even though the property names use `functionName` /
     * `className` (PHP reserved words can't be plain property names in
     * every position).
     */
    public function colorFor(string $kind): Color
    {
        return match ($kind) {
            'comment'  => $this->comment,
            'string'   => $this->string,
            'number'   => $this->number,
            'variable' => $this->variable,
            'keyword'  => $this->keyword,
            'tag'      => $this->tag,
            'constant' => $this->constant,
            'function' => $this->functionName,
            'class'    => $this->className,
            'operator' => $this->operator,
            default    => $this->defaultColor,
        };
    }
}
