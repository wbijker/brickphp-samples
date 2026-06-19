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
use Samples\FlagQuiz\Country;
use Samples\FlagQuiz\Palette;

/**
 * The interactive guess area: the current flag, the text input, and the hint.
 *
 * Owns the live input text. The input element keeps a stable key so focus is
 * preserved across re-renders; the text is cleared whenever the question
 * changes (a new flag code) — which covers correct answers, skips and grid
 * jumps — but kept on a wrong guess so the player can fix their typing.
 */
class GuessPanel extends Component
{
    private string $input = '';
    private string $lastCode = '';

    public function __construct(
        private Country $current,
        private bool $wrong,
        private Closure $onGuess,
        private Closure $onSkip,
        private Closure $onNext,
    ) {}

    protected function initialize(): void
    {
        $this->useState($this->input);
        $this->useState($this->lastCode);
    }

    protected function build(): VNode
    {
        // New question → clear the carried-over text (keeps focus via the
        // stable input key). A wrong guess leaves the code unchanged, so the
        // typed text survives.
        if ($this->current->code !== $this->lastCode) {
            $this->lastCode = $this->current->code;
            $this->input = '';
        }

        return UI::column()
            ->grow()
            ->minHeight(Unit::px(0))
            ->alignMiddle()
            ->content(
                $this->buildFlag(),
                $this->buildInput(),
                $this->buildHint(),
                // Escape/Tab click this (see FlagQuizApp) so a "pass" only
                // reaches the server on those keys — no per-keystroke traffic.
                UI::button('Next')
                    ->attr('id', 'fq-next')
                    ->hidden()
                    ->onClick(fn() => ($this->onNext)()),
            );
    }

    private function buildFlag(): UIElement
    {
        return UI::row()
            ->extendX()
            ->height(Unit::px(288))
            ->alignCenter()
            ->alignMiddle()
            ->padding(Unit::px(36))
            ->content(
                UI::image($this->current->bigUrl(), $this->current->name)
                    ->maxWidth(Unit::full())
                    ->maxHeight(Unit::full())
                    ->objectContain()
                    ->rounded(Unit::px(6))
                    ->shadow(Shadow::Small)
            );
    }

    private function buildInput(): UIElement
    {
        return UI::input()
            ->text()
            ->placeholder('Type the country…')
            ->autocomplete('off')
            ->key('fq-input')
            ->attr('id', 'fq-input')
            ->autofocus()
            ->bind($this->input)
            ->width(Unit::full())
            ->padding(x: Unit::px(30), y: Unit::px(32))
            ->fontSize(FontSize::FourXL)
            ->background($this->wrong ? Palette::redWash() : Palette::white())
            ->bordered(top: 1)
            ->borderColor($this->wrong ? Palette::red() : Palette::border())
            ->outlineNone()
            ->onChange(fn() => ($this->onGuess)($this->input));
    }

    private function buildHint(): UIElement
    {
        return UI::row()
            ->alignMiddle()
            ->gap(Unit::px(8))
            ->bordered(top: 1)
            ->borderColor(Palette::border())
            ->padding(x: Unit::px(22), y: Unit::px(14))
            ->fontSize(FontSize::Small)
            ->color(Palette::subtle())
            ->content(
                UI::text('Enter to submit · Esc to pass'),
                UI::text('·')->color(Palette::dot()),
                UI::button('Skip')
                    ->borderNone()
                    ->background(Palette::transparent())
                    ->color(Palette::blue())
                    ->weight(FontWeight::SemiBold)
                    ->padding(Unit::none())
                    ->clickable()
                    ->onClick(fn() => ($this->onSkip)()),
            );
    }
}
