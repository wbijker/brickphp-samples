<?php

namespace Samples\FlagQuiz\Components;

use Closure;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use Samples\FlagQuiz\Country;
use Samples\FlagQuiz\Palette;

/**
 * The answer area when the flag is what's being asked for. A flag can't be
 * typed, so it is picked: a handful of them, one right, the rest drawn from
 * the same part of the world so the choice is between plausible neighbours
 * rather than between one candidate and five obvious no's.
 *
 * The one clicked in error keeps a red border until the next question, which
 * is the whole correction — you looked at it and it wasn't that one.
 */
class FlagChoices extends Component
{
    /**
     * @param Country[] $choices  the flags on offer, answer among them
     * @param string    $wrongIso the one just picked in error, or ''
     * @param Closure   $onPick   fn(string $iso): void
     */
    public function __construct(
        private array $choices,
        private string $wrongIso,
        private Closure $onPick,
    ) {}

    protected function build(): VNode
    {
        $cells = [];
        foreach ($this->choices as $country) {
            $cells[] = $country->code === $this->wrongIso
                ? $this->wrongCell($country)
                : $this->cell($country);
        }

        return UI::grid(2)
            ->columns(3, Pseudo::sm())
            ->gap(Unit::px(12))
            ->noShrink()
            ->bordered(top: 1)
            ->borderColor(Palette::border())
            ->padding(x: Unit::px(16), y: Unit::px(16))
            ->padding(x: Unit::px(24), y: Unit::px(20), pseudo: Pseudo::sm())
            ->content(...$cells);
    }

    private function cell(Country $country): UIElement
    {
        return UI::row()
            ->alignCenter()
            ->alignMiddle()
            ->background(Palette::white())
            ->bordered()
            ->borderColor(Palette::border())
            ->rounded(Unit::px(10))
            ->padding(Unit::px(8))
            ->shadow(Shadow::Small)
            ->clickable()
            ->onClick(fn() => ($this->onPick)($country->code))
            ->content($this->flag($country));
    }

    private function wrongCell(Country $country): UIElement
    {
        return UI::row()
            ->alignCenter()
            ->alignMiddle()
            ->background(Palette::redWash())
            ->bordered(2)
            ->borderColor(Palette::red())
            ->rounded(Unit::px(10))
            ->padding(Unit::px(8))
            ->shadow(Shadow::Small)
            ->clickable()
            ->onClick(fn() => ($this->onPick)($country->code))
            ->content($this->flag($country));
    }

    /**
     * No alt text and no name anywhere: the flag is the answer, and naming it
     * would hand it over to anything that reads the page — the player included.
     */
    private function flag(Country $country): UIElement
    {
        return UI::image($country->bigUrl(), '')
            ->width(Unit::full())
            ->height(Unit::px(64))
            ->height(Unit::px(84), Pseudo::sm())
            ->objectContain()
            ->rounded(Unit::px(4));
    }
}
