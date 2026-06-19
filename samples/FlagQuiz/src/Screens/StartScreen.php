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
 * The landing screen: logo, title, the game-mode chooser and settings (in a
 * scrolling middle), with the Start button pinned to the bottom.
 */
class StartScreen extends Component
{
    private const MODES = [
        ['flags', 'Flags', 'Name all the flags, one at a time, against the clock.'],
        ['location', 'Locations', 'Find each highlighted country on the world map.'],
    ];

    public function __construct(
        private int $count,
        private string $mode,
        private bool $showFlags,
        private bool $strict,
        private Closure $onStart,
        private Closure $onSelectMode,
        private Closure $onToggleShowFlags,
        private Closure $onToggleStrict,
    ) {}

    protected function build(): VNode
    {
        return UI::column()
            ->grow()
            ->minHeight(Unit::px(0))
            ->content(
                // Scrolling content.
                UI::column()
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
                            ->gap(Unit::px(24))
                            ->content(
                                UI::column()
                                    ->alignCenter()
                                    ->gap(Unit::px(12))
                                    ->content(
                                        new Logo(true),
                                        UI::text('Flagdle')
                                            ->fontSize(FontSize::FiveXL)->weight(FontWeight::SemiBold)->center(),
                                        UI::text('Learn the flags and countries of the world.')
                                            ->center()->fontSize(FontSize::Base)->color(Palette::subtle()),
                                    ),
                                $this->modeChooser(),
                                $this->settings(),
                            )
                    ),
                // Pinned bottom bar.
                UI::row()
                    ->noShrink()
                    ->bordered(top: 1)
                    ->borderColor(Palette::border())
                    ->background(Palette::white())
                    ->padding(Unit::px(20))
                    ->content(
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
                    ),
            );
    }

    private function modeChooser(): UIElement
    {
        $cards = [];
        foreach (self::MODES as [$key, $title, $desc]) {
            $cards[] = $key === $this->mode
                ? $this->modeCardSelected($title, $desc)
                : $this->modeCard($key, $title, $desc);
        }

        return UI::column()
            ->width(Unit::full())
            ->gap(Unit::px(10))
            ->content(
                UI::text('Game mode')
                    ->fontSize(FontSize::ExtraSmall)->uppercase()->color(Palette::labelMuted()),
                UI::row()->wrap()->gap(Unit::px(10))->content(...$cards),
            );
    }

    private function modeCard(string $key, string $title, string $desc): UIElement
    {
        return UI::column()
            ->grow()
            ->minWidth(Unit::px(180))
            ->gap(Unit::px(3))
            ->background(Palette::white())
            ->bordered()
            ->borderColor(Palette::border())
            ->rounded(Unit::px(12))
            ->padding(Unit::px(16))
            ->clickable()
            ->onClick(fn() => ($this->onSelectMode)($key))
            ->content(
                UI::text($title)->weight(FontWeight::SemiBold)->fontSize(FontSize::Base),
                UI::text($desc)->fontSize(FontSize::ExtraSmall)->color(Palette::subtle()),
            );
    }

    private function modeCardSelected(string $title, string $desc): UIElement
    {
        return UI::column()
            ->grow()
            ->minWidth(Unit::px(180))
            ->gap(Unit::px(3))
            ->background(Palette::blueWash())
            ->bordered(2)
            ->borderColor(Palette::blue())
            ->rounded(Unit::px(12))
            ->padding(Unit::px(16))
            ->content(
                UI::text($title)->weight(FontWeight::SemiBold)->fontSize(FontSize::Base),
                UI::text($desc)->fontSize(FontSize::ExtraSmall)->color(Palette::subtle()),
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
                    'Flags mode — list every flag not answered yet',
                    $this->showFlags,
                    $this->onToggleShowFlags,
                ),
                UI::container()->extendX()->height(Unit::px(1))->background(Palette::border()),
                new Toggle(
                    'Strict mode',
                    'One guess per question — a wrong answer is final',
                    $this->strict,
                    $this->onToggleStrict,
                ),
            );
    }
}
