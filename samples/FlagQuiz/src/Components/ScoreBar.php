<?php

namespace Samples\FlagQuiz\Components;

use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use HeroIcons\HeroIcons;
use Samples\FlagQuiz\Palette;

/**
 * The scoring bar at the top of the left panel: flags answered, accuracy, the
 * running right / wrong answer counts, and the elapsed time — each with an icon.
 */
class ScoreBar extends Component
{
    public function __construct(
        private int $answered,
        private int $total,
        private int $score,
        private int $right,
        private int $wrong,
        private string $time,
    ) {}

    protected function build(): VNode
    {
        return UI::row()
            ->noShrink()
            ->bordered(bottom: 1)
            ->borderColor(Palette::border())
            ->content(
                $this->cell(HeroIcons::Flag('none', 1.5, 'currentColor', ''), $this->answered . ' / ' . $this->total, 'Flags', Palette::ink()),
                $this->cell(HeroIcons::ChartBar('none', 1.5, 'currentColor', ''), $this->score . '%', 'Score', Palette::ink()),
                $this->cell(HeroIcons::Check('none', 2, 'currentColor', ''), (string)$this->right, 'Right', Palette::green()),
                $this->cell(HeroIcons::XMark('none', 2, 'currentColor', ''), (string)$this->wrong, 'Wrong', Palette::red()),
                $this->cell(HeroIcons::Clock('none', 1.5, 'currentColor', ''), $this->time, 'Time', Palette::ink()),
            );
    }

    private function cell(VNode $icon, string $value, string $label, Color $valueColor): UIElement
    {
        return UI::column()
            ->grow()
            ->alignCenter()
            ->gap(Unit::px(3))
            ->bordered(right: 1)
            ->borderColor(Palette::border())
            ->padding(x: Unit::px(10), y: Unit::px(13))
            ->content(
                UI::text($value)->weight(FontWeight::SemiBold)->fontSize(FontSize::Large)->color($valueColor)->invalidateText(),
                UI::row()
                    ->alignMiddle()
                    ->gap(Unit::px(5))
                    ->color(Palette::labelMuted())
                    ->content(
                        $icon->attr('width', '13')->attr('height', '13'),
                        UI::text($label)->fontSize(FontSize::ExtraSmall)->uppercase(),
                    ),
            );
    }
}
