<?php

namespace Samples\FlagQuiz\Components;

use Closure;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use Samples\FlagQuiz\Country;
use Samples\FlagQuiz\Palette;

/**
 * Explore mode's left panel: every country (flag + name) as a scrollable,
 * clickable list. Clicking a row focuses the map on that country.
 */
class CountryList extends Component
{
    /**
     * @param Country[] $countries
     * @param Closure   $onSelect fn(string $iso): void
     */
    public function __construct(
        private array $countries,
        private string $selected,
        private Closure $onSelect,
    ) {}

    protected function build(): VNode
    {
        $rows = [];
        foreach ($this->countries as $country) {
            $rows[] = $country->code === $this->selected
                ? $this->rowSelected($country)
                : $this->row($country);
        }

        return UI::column()
            ->width(Unit::full())
            ->width(Unit::px(320), Pseudo::lg())
            ->noShrink(Pseudo::lg())
            ->minHeight(Unit::px(0))
            ->maxHeight(Unit::px(240))
            ->maxHeight(Unit::full(), Pseudo::lg())
            ->scrollableY()
            ->bordered(bottom: 1)
            ->bordered(right: 1, pseudo: Pseudo::lg())
            ->borderColor(Palette::border())
            ->content(...$rows);
    }

    private function row(Country $country): UIElement
    {
        return UI::row()
            ->alignMiddle()
            ->gap(Unit::px(11))
            ->padding(x: Unit::px(16), y: Unit::px(8))
            ->clickable()
            ->background(Palette::blueWash(), Pseudo::hover())
            ->key('c-' . $country->code)
            ->onClick(fn() => ($this->onSelect)($country->code))
            ->content($this->thumb($country), $this->name($country));
    }

    private function rowSelected(Country $country): UIElement
    {
        return UI::row()
            ->alignMiddle()
            ->gap(Unit::px(11))
            ->padding(x: Unit::px(16), y: Unit::px(8))
            ->clickable()
            ->background(Palette::blueWash())
            ->key('c-' . $country->code)
            ->onClick(fn() => ($this->onSelect)($country->code))
            ->content($this->thumb($country), $this->name($country));
    }

    private function thumb(Country $country): UIElement
    {
        return UI::image($country->thumbUrl(), '')
            ->width(Unit::px(30))
            ->height(Unit::px(20))
            ->noShrink()
            ->objectContain()
            ->rounded(Unit::px(3))
            ->bordered()
            ->borderColor(Palette::border());
    }

    private function name(Country $country): UIElement
    {
        return UI::text($country->name)
            ->fontSize(FontSize::Small)
            ->weight(FontWeight::Medium);
    }
}
