<?php

namespace Samples\FlagQuiz\Components;

use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\StatelessComponent;
use BrickPHP\VNode\VNode;
use Samples\FlagQuiz\Palette;

/**
 * The visual on/off switch — a rounded track with a sliding knob. Shared by the
 * settings {@see Toggle} and the inline map controls.
 */
class ToggleSwitch extends StatelessComponent
{
    public function __construct(private bool $on) {}

    protected function build(): VNode
    {
        // The knob is absolutely positioned so its `left` offset can transition
        // (justify-content does not animate). Track colour + knob both slide.
        return UI::row()
            ->relative()
            ->width(Unit::px(46))
            ->height(Unit::em(1.6875))
            ->noShrink()
            ->roundedFull()
            ->animated(200)
            ->background($this->on ? Palette::blue() : Palette::border())
            ->content($this->knob());
    }

    private function knob(): UIElement
    {
        // The on/off offsets are written as separate literal chains: the
        // CssExtractor resolves a ternary's undefined condition to its false
        // branch, so `offsetLeft($on ? 22 : 3)` would only ever emit `left:3px`.
        if ($this->on) {
            return UI::container()
                ->key('knob')
                ->absolute()
                ->offsetTop(Unit::px(3))
                ->offsetLeft(Unit::px(22))
                ->size(Unit::em(1.3125))
                ->roundedFull()
                ->background(Palette::white())
                ->shadow(Shadow::Small)
                ->animated(200);
        }

        return UI::container()
            ->key('knob')
            ->absolute()
            ->offsetTop(Unit::px(3))
            ->offsetLeft(Unit::px(3))
            ->size(Unit::em(1.3125))
            ->roundedFull()
            ->background(Palette::white())
            ->shadow(Shadow::Small)
            ->animated(200);
    }
}
