<?php

namespace Samples\FlagQuiz\Components;

use Closure;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use Samples\FlagQuiz\Logo;
use Samples\FlagQuiz\Palette;

/**
 * The footer pinned to the bottom of the left panel: the Flagdle logo and
 * wordmark, a one-line tagline, and a "Start over" button that restarts the
 * quiz with a fresh shuffle.
 */
class BrandInfo extends Component
{
    public function __construct(
        private Closure $onStartOver,
        private Closure $onDone,
    ) {}

    protected function build(): VNode
    {
        return UI::column()
            ->alignCenter()
            ->gap(Unit::px(11))
            ->bordered(top: 1)
            ->borderColor(Palette::border())
            ->padding(x: Unit::px(22), y: Unit::px(18))
            ->content(
                UI::column()
                    ->alignCenter()
                    ->gap(Unit::px(7))
                    ->content(
                        UI::row()
                            ->alignMiddle()
                            ->gap(Unit::px(9))
                            ->content(
                                new Logo(),
                                UI::text('Flagdle')->weight(FontWeight::SemiBold)->fontSize(FontSize::Base),
                            ),
                        UI::text('Guess every country flag against the clock.')
                            ->center()
                            ->fontSize(FontSize::ExtraSmall)
                            ->color(Palette::subtle()),
                    ),
                UI::row()
                    ->alignMiddle()
                    ->gap(Unit::px(9))
                    ->content(
                        UI::button('Start over')
                            ->background(Palette::white())
                            ->color(Palette::subtle())
                            ->bordered()
                            ->borderColor(Palette::border())
                            ->rounded(Unit::px(10))
                            ->padding(x: Unit::px(18), y: Unit::px(9))
                            ->fontSize(FontSize::Small)
                            ->weight(FontWeight::SemiBold)
                            ->clickable()
                            ->onClick(fn() => ($this->onStartOver)()),
                        UI::button('Done')
                            ->background(Palette::ink())
                            ->color(Palette::white())
                            ->borderNone()
                            ->rounded(Unit::px(10))
                            ->padding(x: Unit::px(18), y: Unit::px(9))
                            ->fontSize(FontSize::Small)
                            ->weight(FontWeight::SemiBold)
                            ->clickable()
                            ->onClick(fn() => ($this->onDone)()),
                    ),
            );
    }
}
