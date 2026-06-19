<?php

namespace Samples\FlagQuiz\Components;

use Closure;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
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
                $this->switch(),
            );
    }

    private function switch(): UIElement
    {
        // The knob is absolutely positioned so its `left` offset can transition
        // (justify-content does not animate). Both the track colour and the
        // knob slide via ->animated().
        return UI::row()
            ->relative()
            ->width(Unit::px(46))
            ->height(Unit::px(27))
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
                ->size(Unit::px(21))
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
            ->size(Unit::px(21))
            ->roundedFull()
            ->background(Palette::white())
            ->shadow(Shadow::Small)
            ->animated(200);
    }
}
