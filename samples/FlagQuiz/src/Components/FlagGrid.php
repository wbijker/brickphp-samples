<?php

namespace Samples\FlagQuiz\Components;

use Closure;
use BrickPHP\UI\FontSize;
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
 * The "not yet answered" panel: a wrapping grid of every remaining flag,
 * including the current one (highlighted as selected). The grid area scrolls
 * within the block on wide screens and flows on narrow ones. Cells transition
 * on hover and as the selection moves; tapping a flag jumps the quiz to it.
 */
class FlagGrid extends Component
{
    /**
     * @param array<array{pos:int, country:Country}> $items
     * @param Closure $onJump fn(int $pos): void
     */
    public function __construct(
        private array $items,
        private int $selected,
        private Closure $onJump,
    ) {}

    protected function build(): VNode
    {
        $cells = [];
        foreach ($this->items as $item) {
            $cells[] = $this->cell($item['pos'], $item['country'], $item['pos'] === $this->selected);
        }

        return UI::column()
            ->grow()
            ->minHeight(Unit::px(0))
            ->padding(top: Unit::px(24), bottom: Unit::px(28), x: Unit::px(32))
            ->gap(Unit::px(14))
            ->content(
                UI::row()
                    ->noShrink()
                    ->alignMiddle()
                    ->gap(Unit::px(9))
                    ->content(
                        UI::text('Not yet answered')
                            ->fontSize(FontSize::ExtraSmall)->uppercase()->color(Palette::labelMuted()),
                        UI::text(count($this->items) . ' left · tap a flag to jump there')
                            ->fontSize(FontSize::ExtraSmall)->color(Palette::footerCount()),
                    ),
                UI::row()
                    ->wrap()
                    ->grow()
                    ->minHeight(Unit::px(0))
                    ->maxHeight(Unit::full())
                    ->scrollableY()
                    ->gap(Unit::px(10))
                    ->content(...$cells),
            );
    }

    private function cell(int $pos, Country $country, bool $selected): UIElement
    {
        // One fluent chain so the CssExtractor can harvest every class.
        // Selected state uses harvestable Color/Shadow ternaries; the hover
        // lift is a literal scale + border so it always emits.
        return UI::row()
            ->width(Unit::px(72))
            ->aspectRatio(3, 2)
            ->background($selected ? Palette::blueWash() : Palette::white())
            ->bordered()
            ->borderColor($selected ? Palette::blue() : Palette::track())
            ->borderColor(Palette::blue(), Pseudo::hover())
            ->shadow($selected ? Shadow::Medium : Shadow::None)
            ->rounded(Unit::px(7))
            ->clipContent()
            ->alignCenter()
            ->alignMiddle()
            ->animated(160)
            ->scale(108, Pseudo::hover())
            ->clickable()
            ->key('cell-' . $pos)
            ->onClick(fn() => ($this->onJump)($pos))
            ->content(
                UI::image($country->thumbUrl(), '')
                    ->maxWidth(Unit::percent(88))
                    ->maxHeight(Unit::percent(88))
                    ->objectContain()
            );
    }
}
