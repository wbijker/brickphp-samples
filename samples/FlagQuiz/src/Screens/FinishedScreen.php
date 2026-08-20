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
use Samples\FlagQuiz\Attribute;
use Samples\FlagQuiz\MissedFlag;
use Samples\FlagQuiz\Palette;

/**
 * The results screen: the final score, summary stats, the questions to review
 * and the play-again / back-to-start actions. What a review chip leads with is
 * whatever the game was asking for — see $asked.
 */
class FinishedScreen extends Component
{
    /**
     * @param Attribute    $asked  what the game was asking for, which is what a
     *   review chip has to lead with: the country's name where that was the
     *   question, its capital where that was.
     * @param MissedFlag[] $missed
     * @param Closure $onRestart     fn(): void
     * @param Closure $onBack        fn(): void
     * @param Closure $onRetryMissed fn(): void — replay just {@see $missed}
     */
    public function __construct(
        private int $correct,
        private int $total,
        private int $accuracy,
        private int $score,
        private string $time,
        private Attribute $asked,
        private array $missed,
        private Closure $onRestart,
        private Closure $onBack,
        private Closure $onRetryMissed,
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
                    // Right out of every flag in the game, not just the ones
                    // reached — that's Accuracy. The headline above already
                    // gives the raw tally this is a percentage of.
                    $this->stat($this->score . '%', 'Score'),
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
            ->minHeight(Unit::em(0))
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

    /**
     * A review chip's text: the right answer, and under it what the player
     * gave — the two words plainly, one above the other. Questions they
     * skipped without answering keep the single line.
     */
    private function buildChipLabel(MissedFlag $miss): UIElement
    {
        // The answer they were after, which isn't always the country's name:
        // asked for a capital, the chip has to lead with the capital, or it
        // reviews a question nobody was asked.
        $answer = $this->asked->textOf($miss->country);
        $name = UI::text($answer)->fontSize(FontSize::Small)->weight(FontWeight::Medium);

        if ($miss->guess === '') {
            return $name;
        }

        return UI::column()
            ->gap(Unit::px(1))
            ->content(
                $name,
                // Same size as the answer above it: the two are the pair being
                // compared, and shrinking one made it read as a footnote. No
                // wording between them — the answer in ink over what they gave
                // in red says which is which.
                UI::text($miss->guess)
                    ->fontSize(FontSize::Small)
                    ->color(Palette::red()),
            );
    }

    private function buildMissed(): UIElement
    {
        $chips = [];
        foreach ($this->missed as $miss) {
            $chips[] = UI::row()
                ->alignMiddle()
                ->gap(Unit::px(9))
                ->background(Palette::white())
                ->bordered()
                ->borderColor(Palette::border())
                ->rounded(Unit::px(10))
                ->padding(left: Unit::px(6), right: Unit::px(13), y: Unit::px(6))
                ->content(
                    UI::image($miss->country->thumbUrl(), '')
                        ->width(Unit::px(32))
                        ->height(Unit::px(22))
                        ->objectContain()
                        ->rounded(Unit::px(3)),
                    $this->buildChipLabel($miss),
                );
        }

        return UI::column()
            ->width(Unit::full())
            ->maxWidth(Unit::px(780))
            ->gap(Unit::px(11))
            ->content(
                // The action belongs to this section, not to the pair of
                // buttons below: it plays the flags listed right here, and
                // reads as a caption on them rather than a third way to
                // start a game.
                UI::row()
                    ->alignMiddle()
                    ->alignBetween()
                    ->gap(Unit::px(12))
                    ->content(
                        UI::text('To review — ' . count($this->missed))
                            ->fontSize(FontSize::ExtraSmall)->uppercase()->color(Palette::labelMuted()),
                        UI::button('Practise these ' . count($this->missed) . ' →')
                            ->noShrink()
                            ->borderNone()
                            ->background(Palette::transparent())
                            ->color(Palette::blue())
                            ->weight(FontWeight::SemiBold)
                            ->fontSize(FontSize::Small)
                            ->padding(Unit::none())
                            ->clickable()
                            ->onClick(fn() => ($this->onRetryMissed)()),
                    ),
                UI::row()
                    ->wrap()
                    ->gap(Unit::px(9))
                    ->maxHeight(Unit::em(14.375))
                    ->scrollableY()
                    ->content(...$chips),
            );
    }
}
