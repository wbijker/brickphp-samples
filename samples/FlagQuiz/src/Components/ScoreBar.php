<?php

namespace Samples\FlagQuiz\Components;

use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use HeroIcons\HeroIcons;
use Samples\FlagQuiz\Answer;
use Samples\FlagQuiz\Palette;

/**
 * The scoring header of the left panel: the last-5 attempts strip on top, then
 * a row of stats — flags answered, accuracy, the running right / wrong counts,
 * and the elapsed time, each with an icon. The values shrink a step on phones
 * so all five cells fit a narrow row without overflow.
 */
class ScoreBar extends Component
{
    /** @param Answer[] $recent last 5 attempt outcomes for the history strip */
    public function __construct(
        private int $answered,
        private int $total,
        private int $score,
        private int $right,
        private int $wrong,
        private string $time,
        private array $recent,
    ) {}

    protected function build(): VNode
    {
        return UI::column()
            ->noShrink()
            ->bordered(bottom: 1)
            ->borderColor(Palette::border())
            ->content(
                UI::row()
                    ->alignCenter()
                    ->bordered(bottom: 1)
                    ->borderColor(Palette::border())
                    ->padding(x: Unit::px(16), y: Unit::px(11))
                    ->content(new AttemptHistory($this->recent)),
                UI::row()->content(
                    $this->cell(HeroIcons::Flag('none', 1.5, 'currentColor', ''), $this->answered . ' / ' . $this->total, 'Flags', StatTone::Ink),
                    $this->cell(HeroIcons::ChartBar('none', 1.5, 'currentColor', ''), $this->score . '%', 'Score', StatTone::Ink),
                    $this->cell(HeroIcons::Check('none', 2, 'currentColor', ''), (string)$this->right, 'Right', StatTone::Right),
                    $this->cell(HeroIcons::XMark('none', 2, 'currentColor', ''), (string)$this->wrong, 'Wrong', StatTone::Wrong),
                    $this->cell(HeroIcons::Clock('none', 1.5, 'currentColor', ''), $this->time, 'Time', StatTone::Ink),
                ),
            );
    }

    private function cell(VNode $icon, string $value, string $label, StatTone $tone): UIElement
    {
        $labelRow = UI::row()
            ->alignMiddle()
            ->gap(Unit::px(5))
            ->color(Palette::labelMuted())
            ->content(
                $icon->attr('width', '13')->attr('height', '13'),
                UI::text($label)->fontSize(FontSize::ExtraSmall)->uppercase(),
            );

        if (!$tone->isTally()) {
            return UI::column()
                ->grow()
                ->alignCenter()
                ->gap(Unit::px(3))
                ->bordered(right: 1)
                ->borderColor(Palette::border())
                ->padding(x: Unit::px(8), y: Unit::px(13))
                ->padding(x: Unit::px(10), pseudo: Pseudo::sm())
                ->content(
                    UI::text($value)
                        ->weight(FontWeight::SemiBold)
                        ->fontSize(FontSize::Base)
                        ->fontSize(FontSize::Large, Pseudo::sm())
                        ->color(Palette::ink())
                        ->invalidateText(),
                    $labelRow,
                );
        }

        // Right / wrong tally: both children are keyed so a changed value
        // re-keys the number, which makes the diff replace (not patch) the
        // node — re-creating it restarts the `fq-pop` animation.
        return UI::column()
            ->grow()
            ->alignCenter()
            ->gap(Unit::px(3))
            ->bordered(right: 1)
            ->borderColor(Palette::border())
            ->padding(x: Unit::px(8), y: Unit::px(13))
            ->padding(x: Unit::px(10), pseudo: Pseudo::sm())
            ->content(
                UI::text($value)
                    ->weight(FontWeight::SemiBold)
                    ->fontSize(FontSize::Base)
                    ->fontSize(FontSize::Large, Pseudo::sm())
                    ->color($tone === StatTone::Right ? Palette::green() : Palette::red())
                    ->class('fq-pop')
                    ->key($tone->name . '-val-' . $value),
                $labelRow->key($tone->name . '-label'),
            );
    }
}
