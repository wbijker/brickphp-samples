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
use Samples\FlagQuiz\RemainingFlag;

/**
 * The "not yet answered" panel: a wrapping grid of every remaining flag,
 * including the current one (highlighted as selected). The grid area scrolls
 * within the block on wide screens and flows on narrow ones. Cells transition
 * on hover and as the selection moves; tapping a flag jumps the quiz to it.
 */
class FlagGrid extends Component
{
    /**
     * @param RemainingFlag[] $items
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
            $cells[] = $item->pos === $this->selected
                ? $this->selectedCell($item->pos, $item->country)
                : $this->cell($item->pos, $item->country);
        }

        return UI::column()
            ->grow()
            ->minHeight(Unit::px(0))
            ->padding(top: Unit::px(20), bottom: Unit::px(24), x: Unit::px(16))
            ->padding(top: Unit::px(24), bottom: Unit::px(28), x: Unit::px(32), pseudo: Pseudo::lg())
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
                // The scroll area grows/scrolls; the wrapping cells inside keep
                // their natural height so flags pack at the top rather than
                // stretching to fill the panel.
                UI::column()
                    ->grow()
                    ->padding(2)
                    ->minHeight(Unit::px(0))
                    ->scrollableY()
                    ->content(
                        UI::row()
                            ->wrap()
                            ->alignTop()
                            ->gap(Unit::px(10))
                            ->content(...$cells),
                    ),
            );
    }

    private function cell(int $pos, Country $country): UIElement
    {
        return UI::row()
            ->width(Unit::px(72))
            ->aspectRatio(3, 2)
            ->background(Palette::white())
            ->bordered()
            ->borderColor(Palette::track())
            ->borderColor(Palette::blue(), Pseudo::hover())
            ->rounded(Unit::px(7))
            ->clipContent()
            ->alignCenter()
            ->alignMiddle()
            ->animated(160)
            ->scale(108, Pseudo::hover())
            ->clickable()
            ->key('cell-' . $pos)
            ->onClick(fn() => ($this->onJump)($pos))
            ->content($this->thumb($country));
    }

    /**
     * The current flag, made clearly distinct: scaled up, a thick blue ring,
     * a blue wash and a strong shadow. Written as its own literal chain so the
     * `scale`/`border-2` classes are harvested (they can't come from a ternary).
     */
    private function selectedCell(int $pos, Country $country): UIElement
    {
        return UI::row()
            ->width(Unit::px(72))
            ->aspectRatio(3, 2)
            ->background(Palette::blueWash())
            ->bordered(2)
            ->borderColor(Palette::blue())
            ->rounded(Unit::px(8))
            ->clipContent()
            ->alignCenter()
            ->alignMiddle()
            ->animated(160)
            ->scale(118)
            ->zIndex(10)
            ->shadow(Shadow::ExtraLarge)
            ->clickable()
            ->key('cell-' . $pos)
            ->onClick(fn() => ($this->onJump)($pos))
            ->content($this->thumb($country));
    }

    private function thumb(Country $country): UIElement
    {
        return UI::image($country->thumbUrl(), '')
            ->maxWidth(Unit::percent(88))
            ->maxHeight(Unit::percent(88))
            ->objectContain();
    }
}
