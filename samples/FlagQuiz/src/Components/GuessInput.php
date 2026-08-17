<?php

namespace Samples\FlagQuiz\Components;

use Closure;
use BrickPHP\Events\Key;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use Samples\FlagQuiz\Palette;

/**
 * The answer field shared by every "name the country" mode: a big text input,
 * the hint row (with Skip), and the hidden "Next" button that Escape/Tab click.
 *
 * Owns the live input text. The input keeps a stable id/key so focus survives
 * re-renders; the text is cleared whenever the question changes (a new $code) —
 * covering correct answers, skips and jumps — but is kept on a wrong guess so
 * the player can fix their typing.
 */
class GuessInput extends Component
{
    private string $input = '';
    private string $lastCode = '';

    public function __construct(
        private string $code,
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
        if ($this->code !== $this->lastCode) {
            $this->lastCode = $this->code;
            $this->input = '';
        }

        return UI::column()
            // Escape passes on the current flag from anywhere on the screen —
            // a document-level handler, because the player may have clicked
            // away into the grid or the map. preventDefault so the key is ours
            // alone. Only Escape ever leaves the browser; every other keystroke
            // is filtered on the client and never becomes a request.
            ->onGlobalKeyDown(fn() => ($this->onNext)(), [Key::Escape], preventDefault: true)
            ->content(
                $this->buildInput(),
                $this->buildHint(),
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
            // Tab passes too, but only while typing — so it binds to the field
            // itself rather than the document, and preventDefault keeps focus
            // here instead of moving it to the next control.
            ->onKeyDown(fn() => ($this->onNext)(), [Key::Tab], preventDefault: true)
            ->bind($this->input)
            ->width(Unit::full())
            ->padding(x: Unit::px(20), y: Unit::px(18))
            ->padding(x: Unit::px(30), y: Unit::px(26), pseudo: Pseudo::sm())
            ->fontSize(FontSize::TwoXL)
            ->fontSize(FontSize::ThreeXL, Pseudo::sm())
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
