<?php

namespace Samples\FlagQuiz\Screens;

use Closure;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
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
 * The results screen: the final score, summary stats, the flags to review and
 * the play-again / back-to-start actions.
 */
class FinishedScreen extends Component
{
    /**
     * @param Country[] $missed
     * @param Closure $onRestart fn(): void
     * @param Closure $onBack    fn(): void
     */
    public function __construct(
        private int $correct,
        private int $total,
        private int $accuracy,
        private string $time,
        private array $missed,
        private Closure $onRestart,
        private Closure $onBack,
    ) {}

    protected function build(): VNode
    {
        $children = [
            UI::text('Quiz complete')->fontSize(FontSize::Small)->uppercase()->color(Palette::labelMuted()),
            UI::row()
                ->alignMiddle()
                ->content(
                    UI::text((string)$this->correct)->fontSize(FontSize::SixXL)->weight(FontWeight::SemiBold),
                    UI::text(' / ' . $this->total)
                        ->fontSize(FontSize::SixXL)->weight(FontWeight::SemiBold)->color(Palette::footerCount()),
                ),
            UI::row()
                ->wrap()
                ->alignMiddle()
                ->gap(Unit::px(13))
                ->content(
                    $this->stat($this->accuracy . '%', 'Accuracy'),
                    $this->stat($this->time, 'Time'),
                    $this->stat((string)$this->correct, 'Correct'),
                ),
        ];

        if (count($this->missed) > 0) {
            $children[] = $this->buildMissed();
        }

        $children[] = UI::column()
            ->alignCenter()
            ->gap(Unit::px(14))
            ->content(
                UI::button('Play again')
                    ->background(Palette::ink())
                    ->color(Palette::white())
                    ->borderNone()
                    ->rounded(Unit::px(13))
                    ->padding(x: Unit::px(34), y: Unit::px(15))
                    ->weight(FontWeight::SemiBold)
                    ->fontSize(FontSize::Base)
                    ->shadow(Shadow::Large)
                    ->clickable()
                    ->onClick(fn() => ($this->onRestart)()),
                UI::button('Back to start')
                    ->borderNone()
                    ->background(Palette::transparent())
                    ->color(Palette::blue())
                    ->weight(FontWeight::SemiBold)
                    ->fontSize(FontSize::Small)
                    ->padding(Unit::none())
                    ->clickable()
                    ->onClick(fn() => ($this->onBack)()),
            );

        return UI::column()
            ->grow()
            ->minHeight(Unit::px(0))
            ->scrollableY()
            ->alignCenter()
            ->alignMiddle()
            ->gap(Unit::px(20))
            ->gap(Unit::px(26), Pseudo::sm())
            ->padding(Unit::px(24))
            ->padding(Unit::px(48), Pseudo::sm())
            ->content(...$children);
    }

    private function stat(string $value, string $label): UIElement
    {
        return UI::column()
            ->alignCenter()
            ->gap(Unit::px(3))
            ->background(Palette::white())
            ->bordered()
            ->borderColor(Palette::border())
            ->rounded(Unit::px(15))
            ->padding(x: Unit::px(26), y: Unit::px(16))
            ->minWidth(Unit::px(118))
            ->content(
                UI::text($value)->weight(FontWeight::SemiBold)->fontSize(FontSize::TwoXL),
                UI::text($label)->fontSize(FontSize::ExtraSmall)->uppercase()->color(Palette::labelMuted()),
            );
    }

    private function buildMissed(): UIElement
    {
        $chips = [];
        foreach ($this->missed as $c) {
            $chips[] = UI::row()
                ->alignMiddle()
                ->gap(Unit::px(9))
                ->background(Palette::white())
                ->bordered()
                ->borderColor(Palette::border())
                ->rounded(Unit::px(10))
                ->padding(left: Unit::px(6), right: Unit::px(13), y: Unit::px(6))
                ->content(
                    UI::image($c->thumbUrl(), '')
                        ->width(Unit::px(32))
                        ->height(Unit::px(22))
                        ->objectContain()
                        ->rounded(Unit::px(3)),
                    UI::text($c->name)->fontSize(FontSize::Small)->weight(FontWeight::Medium),
                );
        }

        return UI::column()
            ->width(Unit::full())
            ->maxWidth(Unit::px(780))
            ->gap(Unit::px(11))
            ->content(
                UI::text('Flags to review — ' . count($this->missed))
                    ->fontSize(FontSize::ExtraSmall)->uppercase()->color(Palette::labelMuted()),
                UI::row()
                    ->wrap()
                    ->gap(Unit::px(9))
                    ->maxHeight(Unit::px(230))
                    ->scrollableY()
                    ->content(...$chips),
            );
    }
}
