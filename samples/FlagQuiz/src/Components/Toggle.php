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
 * A labelled on/off switch used on the start screen for the game settings.
 * Clicking the row toggles it; the knob slides with a transition.
 */
class Toggle extends Component
{
    public function __construct(
        private string $label,
        private string $desc,
        private bool $on,
        private Closure $onToggle,
    ) {}

    protected function build(): VNode
    {
        return UI::row()
            ->alignMiddle()
            ->alignBetween()
            ->gap(Unit::px(16))
            ->padding(x: Unit::px(20), y: Unit::px(16))
            ->clickable()
            ->onClick(fn() => ($this->onToggle)())
            ->content(
                UI::column()
                    ->grow()
                    ->gap(Unit::px(2))
                    ->content(
                        UI::text($this->label)->weight(FontWeight::SemiBold)->fontSize(FontSize::Small),
                        UI::text($this->desc)->fontSize(FontSize::ExtraSmall)->color(Palette::subtle()),
                    ),
                new ToggleSwitch($this->on),
            );
    }
}
