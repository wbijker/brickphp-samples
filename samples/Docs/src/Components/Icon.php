<?php

namespace Samples\Docs\Components;

use BrickPHP\UI\Color;
use BrickPHP\UI\UI;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;

/**
 * Material Symbols Outlined icon. Pass the icon name (e.g. `dataset`,
 * `link_off`, `bolt`) and optional size/color.
 *
 *   new Icon('dataset')
 *   (new Icon('check_circle'))->color(Color::orange(600))->size(20)
 *
 * The font is registered in DocsApp::registerAssets — the `.material-
 * symbols-outlined` class on the span tells the font to render the text
 * as a glyph rather than literal characters.
 */
class Icon extends Component
{
    private int $size = 24;
    private ?Color $color = null;
    private bool $filled = true;

    public function __construct(private string $name) {}

    public function size(int $px): static
    {
        $this->size = $px;
        return $this;
    }

    public function color(Color $color): static
    {
        $this->color = $color;
        return $this;
    }

    /** Toggle the FILL axis of the variable font. */
    public function outlined(): static
    {
        $this->filled = false;
        return $this;
    }

    protected function build(): VNode
    {
        $span = UI::span($this->name)
            ->class('material-symbols-outlined')
            ->attr('style', sprintf(
                'font-size:%dpx;line-height:1;font-variation-settings:"FILL" %d,"wght" 400,"GRAD" 0,"opsz" %d;',
                $this->size,
                $this->filled ? 1 : 0,
                min(48, max(20, $this->size)),
            ));

        if ($this->color !== null) {
            $span = $span->color($this->color);
        }

        return $span;
    }
}
