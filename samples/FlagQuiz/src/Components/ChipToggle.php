<?php

namespace Samples\FlagQuiz\Components;

use Closure;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use Samples\FlagQuiz\Palette;

/**
 * A compact inline label + switch, used for the on-map controls (e.g. the
 * auto-zoom toggle). Clicking anywhere on the chip flips it.
 */
class ChipToggle extends Component
{
    public function __construct(
        private string $label,
        private bool $on,
        private Closure $onToggle,
    ) {}

    protected function build(): VNode
    {
        return UI::row()
            ->noShrink()
            ->alignMiddle()
            ->gap(Unit::px(8))
            ->clickable()
            ->onClick(fn() => ($this->onToggle)())
            ->content(
                UI::text($this->label)
                    ->fontSize(FontSize::Small)
                    ->weight(FontWeight::Medium)
                    ->color(Palette::subtle()),
                new ToggleSwitch($this->on),
            );
    }
}
