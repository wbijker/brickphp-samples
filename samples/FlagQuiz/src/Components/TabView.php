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
 * A row of tabs over the one that is open.
 *
 * Drawn as a segmented control — a tray of pills with the open one raised —
 * rather than as underlined labels. The row wraps on a narrow screen, and a
 * shared underline is exactly what cannot survive wrapping: the second line of
 * tabs would sit under a rule belonging to the first.
 *
 * Only the open tab's content is passed in. Building all of them to show one
 * is work thrown away, and the caller knows which is open before it starts.
 */
class TabView extends Component
{
    /**
     * @param Tab[]   $tabs
     * @param string  $active   the key of the open tab
     * @param Closure $onSelect fn(string $key): void
     */
    public function __construct(
        private array $tabs,
        private string $active,
        private Closure $onSelect,
        private VNode $content,
    ) {}

    protected function build(): VNode
    {
        $pills = [];
        foreach ($this->tabs as $tab) {
            $pills[] = $tab->key === $this->active ? $this->openTab($tab) : $this->closedTab($tab);
        }

        return UI::column()
            ->width(Unit::full())
            ->gap(Unit::px(16))
            ->content(
                UI::row()
                    ->wrap()
                    ->gap(Unit::px(4))
                    ->background(Palette::page())
                    ->rounded(Unit::px(12))
                    ->padding(Unit::px(4))
                    ->content(...$pills),
                $this->content,
            );
    }

    // The two states are written as separate literal chains: an open tab and a
    // closed one are different things to look at, and they read better side by
    // side than as one chain with three ternaries in it.

    private function openTab(Tab $tab): UIElement
    {
        return UI::row()
            ->noShrink()
            ->alignMiddle()
            ->background(Palette::white())
            ->rounded(Unit::px(9))
            ->shadow(Shadow::Small)
            ->padding(x: Unit::px(14), y: Unit::px(8))
            ->content(
                UI::text($tab->label)
                    ->fontSize(FontSize::Small)->weight(FontWeight::SemiBold)->color(Palette::ink()),
            );
    }

    private function closedTab(Tab $tab): UIElement
    {
        return UI::row()
            ->noShrink()
            ->alignMiddle()
            ->rounded(Unit::px(9))
            ->padding(x: Unit::px(14), y: Unit::px(8))
            ->clickable()
            ->onClick(fn() => ($this->onSelect)($tab->key))
            ->content(
                UI::text($tab->label)
                    ->fontSize(FontSize::Small)->weight(FontWeight::Medium)->color(Palette::subtle()),
            );
    }
}
