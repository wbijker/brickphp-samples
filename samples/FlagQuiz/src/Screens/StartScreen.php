<?php

namespace Samples\FlagQuiz\Screens;

use Closure;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use Samples\FlagQuiz\Components\Toggle;
use Samples\FlagQuiz\Logo;
use Samples\FlagQuiz\Palette;

/**
 * The landing screen: logo, title, intro, the game settings, and the Start
 * button.
 */
class StartScreen extends Component
{
    public function __construct(
        private int $count,
        private bool $showFlags,
        private bool $strict,
        private Closure $onStart,
        private Closure $onToggleShowFlags,
        private Closure $onToggleStrict,
    ) {}

    protected function build(): VNode
    {
        return UI::column()
            ->grow()
            ->minHeight(Unit::px(0))
            ->scrollableY()
            ->alignCenter()
            ->alignMiddle()
            ->padding(Unit::px(40))
            ->content(
                UI::column()
                    ->width(Unit::full())
                    ->maxWidth(Unit::px(460))
                    ->alignCenter()
                    ->gap(Unit::px(26))
                    ->content(
                        UI::column()
                            ->alignCenter()
                            ->gap(Unit::px(14))
                            ->content(
                                new Logo(true),
                                UI::text('Flagdle')
                                    ->fontSize(FontSize::FiveXL)->weight(FontWeight::SemiBold)->center(),
                                UI::text('Name all ' . $this->count . ' flags, one at a time, against the clock.')
                                    ->center()
                                    ->fontSize(FontSize::Base)
                                    ->color(Palette::subtle())
                                    ->maxWidth(Unit::px(340)),
                            ),
                        $this->settings(),
                        UI::button('Start quiz')
                            ->width(Unit::full())
                            ->background(Palette::ink())
                            ->color(Palette::white())
                            ->borderNone()
                            ->rounded(Unit::px(14))
                            ->padding(Unit::px(17))
                            ->weight(FontWeight::SemiBold)
                            ->fontSize(FontSize::Large)
                            ->shadow(Shadow::Large)
                            ->clickable()
                            ->onClick(fn() => ($this->onStart)()),
                    )
            );
    }

    private function settings(): UIElement
    {
        return UI::column()
            ->width(Unit::full())
            ->background(Palette::white())
            ->bordered()
            ->borderColor(Palette::border())
            ->rounded(Unit::px(16))
            ->clipContent()
            ->content(
                new Toggle(
                    'Show available flags',
                    'List every flag you have not answered yet',
                    $this->showFlags,
                    $this->onToggleShowFlags,
                ),
                UI::container()->extendX()->height(Unit::px(1))->background(Palette::border()),
                new Toggle(
                    'Strict mode',
                    'One guess per flag — a wrong answer is final',
                    $this->strict,
                    $this->onToggleStrict,
                ),
            );
    }
}
