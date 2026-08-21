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
use Samples\FlagQuiz\Attribute;
use Samples\FlagQuiz\Country;
use Samples\FlagQuiz\Palette;

/**
 * Explore mode's band of facts about the focused country — everything it is
 * known for, read straight off — over a row of chips saying which of them the
 * map should draw.
 *
 * The two halves answer different questions and so are not tied to each other.
 * Pick a country and you are told all of it: landmarks, rivers, mountain
 * ranges, big waters, population. The chips are about the map underneath —
 * whether to pin the landmarks, trace the rivers, shade the ranges and the
 * lakes, and whether to draw the countries at all — and turning one off hides
 * that layer from the map without hiding anything from the reading above it.
 *
 * The chips apply with or without a country picked. Nothing picked, they draw
 * the world's: every river, every range, every landmark in play. Pick one and
 * they narrow to it. So the chips are a way of looking at the whole map, not
 * only a way of annotating a country.
 *
 * A population has no chip. A landmark is somewhere, a river runs a course, a
 * range and a lake cover ground; a population is a number about the whole
 * country with no place of its own, so there is nothing for the map to draw
 * and no chip to draw it with.
 *
 * Every fact gets its cell, even where the country carries nothing for it:
 * Malta reads "MAJOR RIVER — None", because the honest answer to what rivers
 * Malta has is that it has none, and a missing cell would read as the strip
 * being broken.
 */
class FactStrip extends Component
{
    /**
     * @param ?Country    $country the focused country, or null while none is
     * @param Attribute[] $layers  the facts the map is drawing
     * @param Closure     $onToggle fn(Attribute $fact): void
     */
    public function __construct(
        private ?Country $country,
        private array $layers,
        private Closure $onToggle,
    ) {}

    protected function build(): VNode
    {
        $chips = [];
        foreach (Attribute::layers() as $fact) {
            $chips[] = in_array($fact, $this->layers, true)
                ? $this->chipOn($fact)
                : $this->chipOff($fact);
        }

        $children = [
            UI::row()
                ->wrap()
                ->alignMiddle()
                ->gap(Unit::px(7))
                ->content(
                    UI::text('Show on map')
                        ->noShrink()
                        ->fontSize(FontSize::ExtraSmall)->uppercase()->color(Palette::labelMuted()),
                    ...$chips,
                ),
        ];
        if ($this->country !== null) {
            $children[] = UI::row()->wrap()->gap(Unit::px(22))->content(...$this->cells());
        }

        return UI::column()
            ->noShrink()
            ->gap(Unit::px(11))
            ->background(Palette::offWhite())
            ->bordered(bottom: 1)
            ->borderColor(Palette::border())
            ->padding(x: Unit::px(16), y: Unit::px(12))
            ->padding(x: Unit::px(20), pseudo: Pseudo::lg())
            ->content(...$children);
    }

    /**
     * A cell for every fact, in the order the lists put them. Not filtered by
     * the chips: those say what the map draws, and a country you have clicked
     * on tells you everything it is.
     *
     * @return UIElement[]
     */
    private function cells(): array
    {
        $country = $this->country;
        $cells = [];
        foreach (Attribute::facts() as $fact) {
            $cells[] = UI::column()
                ->gap(Unit::px(2))
                ->content(
                    UI::text($fact->label())
                        ->fontSize(FontSize::ExtraSmall)->uppercase()->color(Palette::labelMuted()),
                    $fact->hasData($country)
                        ? UI::text($fact->textOf($country))
                            ->fontSize(FontSize::Small)->weight(FontWeight::Medium)
                        : UI::text('None')
                            ->fontSize(FontSize::Small)->color(Palette::labelMuted()),
                );
        }
        return $cells;
    }

    // The two chip states are written as separate literal chains: each is a
    // different thing to look at rather than one thing with a colour swapped,
    // and they read better side by side than as a row of ternaries.

    private function chipOn(Attribute $fact): UIElement
    {
        return UI::row()
            ->noShrink()
            ->alignMiddle()
            ->background(Palette::blue())
            ->bordered()
            ->borderColor(Palette::blue())
            ->roundedFull()
            ->padding(x: Unit::px(11), y: Unit::px(5))
            ->clickable()
            ->onClick(fn() => ($this->onToggle)($fact))
            ->content(
                UI::text($fact->layerLabel())
                    ->fontSize(FontSize::ExtraSmall)->weight(FontWeight::SemiBold)->color(Palette::white()),
            );
    }

    private function chipOff(Attribute $fact): UIElement
    {
        return UI::row()
            ->noShrink()
            ->alignMiddle()
            ->background(Palette::white())
            ->bordered()
            ->borderColor(Palette::border())
            ->roundedFull()
            ->padding(x: Unit::px(11), y: Unit::px(5))
            ->clickable()
            ->onClick(fn() => ($this->onToggle)($fact))
            ->content(
                UI::text($fact->layerLabel())
                    ->fontSize(FontSize::ExtraSmall)->weight(FontWeight::Medium)->color(Palette::subtle()),
            );
    }
}
