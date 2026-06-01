<?php

namespace Samples\Docs\Components;

use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;

/**
 * BrickPHP logo — the design's PNG mark served from `/assets/images/`.
 * Wrapped in a Component so callers always go through the same API
 * (`->size($px)`) and we can swap the underlying asset (PNG vs SVG vs
 * inline data URI) without touching call sites.
 */
class BrickLogo extends Component
{
    private const SRC = '/assets/images/brick-logo.png';

    private int $size = 56;

    public function size(int $px): static
    {
        $this->size = $px;
        return $this;
    }

    protected function build(): VNode
    {
        return UI::image(self::SRC, 'BrickPHP')
            ->width(Unit::px($this->size))
            ->height(Unit::px($this->size));
    }
}
