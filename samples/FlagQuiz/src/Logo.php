<?php

namespace Samples\FlagQuiz;

use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;

/**
 * The Flagdle mark: a small blue "flag" with a darker hoist stripe. Two sizes —
 * the large hero variant for the start screen and the small inline variant for
 * the brand info. Each variant inlines its size literals so the CssExtractor
 * can harvest them (a passed-in Unit would not be a literal it can read).
 */
class Logo extends Component
{
    public function __construct(private bool $large = false) {}

    protected function build(): VNode
    {
        return $this->large ? $this->large() : $this->small();
    }

    private function large(): VNode
    {
        return UI::row()
            ->width(Unit::px(60))
            ->height(Unit::px(40))
            ->rounded(Unit::px(7))
            ->background(Palette::blue())
            ->shadow(Shadow::Large)
            ->clipContent()
            ->content(
                UI::container()->width(Unit::px(11))->extendY()->background(Palette::blueDark())
            );
    }

    private function small(): VNode
    {
        return UI::row()
            ->width(Unit::px(24))
            ->height(Unit::px(16))
            ->rounded(Unit::px(3))
            ->background(Palette::blue())
            ->shadow(Shadow::Small)
            ->clipContent()
            ->content(
                UI::container()->width(Unit::px(5))->extendY()->background(Palette::blueDark())
            );
    }
}
