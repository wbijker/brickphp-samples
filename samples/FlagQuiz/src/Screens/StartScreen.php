<?php

namespace Samples\FlagQuiz\Screens;

use Closure;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use Samples\FlagQuiz\Components\Toggle;
use Samples\FlagQuiz\GameMode;
use Samples\FlagQuiz\Logo;
use Samples\FlagQuiz\Palette;

/**
 * The landing screen: logo, title, the game-mode chooser and settings (in a
 * scrolling middle), with the Start button pinned to the bottom. The mode cards
 * are driven straight off {@see GameMode}, which carries each mode's own copy.
 */
class StartScreen extends Component
{
    /**
     * @param Closure $onStart           fn(): void
     * @param Closure $onSelectMode      fn(GameMode $mode): void
     * @param Closure $onToggleShowFlags fn(): void
     * @param Closure $onToggleStrict    fn(): void
     */
    public function __construct(
        private int $count,
        private GameMode $mode,
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
                // Scrolling content. Roomier padding from the sm breakpoint up.
                UI::column()
                    ->grow()
                    ->minHeight(Unit::px(0))
                    ->scrollableY()
                    ->alignCenter()
                    ->alignMiddle()
                    ->padding(Unit::px(24))
                    ->padding(Unit::px(40), Pseudo::sm())
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
                                            ->fontSize(FontSize::FourXL)->fontSize(FontSize::FiveXL, Pseudo::sm())
                                            ->weight(FontWeight::SemiBold)->center(),
                                        UI::text('Learn the flags and countries of the world.')
                                            ->center()->fontSize(FontSize::Base)->color(Palette::subtle()),
                                    ),
                                $this->modeChooser(),
                                ...($this->mode->hasSettings() ? [$this->settings()] : []),
                            )
                    ),
                // Pinned bottom bar.
                UI::row()
                    ->noShrink()
                    ->bordered(top: 1)
                    ->borderColor(Palette::border())
                    ->background(Palette::white())
                    ->padding(Unit::px(16))
                    ->padding(Unit::px(20), Pseudo::sm())
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
        foreach (GameMode::cases() as $mode) {
            $cards[] = $mode === $this->mode
                ? $this->modeCardSelected($mode)
                : $this->modeCard($mode);
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

    private function modeCard(GameMode $mode): UIElement
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
            ->onClick(fn() => ($this->onSelectMode)($mode))
            ->content(
                UI::text($mode->title())->weight(FontWeight::SemiBold)->fontSize(FontSize::Base),
                UI::text($mode->description())->fontSize(FontSize::ExtraSmall)->color(Palette::subtle()),
            );
    }

    private function modeCardSelected(GameMode $mode): UIElement
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
                UI::text($mode->title())->weight(FontWeight::SemiBold)->fontSize(FontSize::Base),
                UI::text($mode->description())->fontSize(FontSize::ExtraSmall)->color(Palette::subtle()),
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
                    $this->mode->navToggleLabel(),
                    $this->mode->navToggleDescription(),
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
