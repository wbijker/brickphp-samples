<?php

namespace Samples\FlagQuiz\Screens;

use Closure;
use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use Samples\FlagQuiz\DiffKind;
use Samples\FlagQuiz\GuessDiff;
use Samples\FlagQuiz\GuessKind;
use Samples\FlagQuiz\MissedFlag;
use Samples\FlagQuiz\Palette;

/**
 * The results screen: the final score, summary stats, the flags to review and
 * the play-again / back-to-start actions.
 */
class FinishedScreen extends Component
{
    /**
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
     * typed — aligned against the answer by {@see GuessDiff}, so the letters
     * they got carry no colour and only the edits between the two words do.
     * Flags they skipped without typing anything keep the single line.
     */
    private function buildChipLabel(MissedFlag $miss): UIElement
    {
        $name = UI::text($miss->country->name)->fontSize(FontSize::Small)->weight(FontWeight::Medium);

        if ($miss->guess === '') {
            return $name;
        }

        // A pinpointed miss is a different country, not a misspelling of this
        // one — so it is named rather than aligned letter by letter.
        if ($miss->kind === GuessKind::Picked) {
            return UI::column()
                ->gap(Unit::px(1))
                ->content(
                    $name,
                    UI::text('you picked ' . $miss->guess)
                        ->fontSize(FontSize::Small)
                        ->color(Palette::red()),
                );
        }

        // Two chains rather than a conditional ->strikethrough() on a stored
        // one: the CssExtractor only harvests classes it can see in a literal
        // chain (see FlagQuiz::buildLeftPanel for the same dance).
        // preserveWhitespace() on both — spaces are letters here too, and the
        // run either side of one is a separate node, so the gap between words
        // would otherwise collapse.
        $letters = [];
        foreach (GuessDiff::compare($miss->country->name, $miss->guess) as $part) {
            if ($part->kind === DiffKind::Added) {
                // Struck out as well as red: these are letters the player put
                // there that the name has no room for, and the rule is what
                // says "delete this" rather than merely "this is wrong".
                $letters[] = UI::text($part->text)
                    ->preserveWhitespace()
                    ->strikethrough()
                    ->fontSize(FontSize::Small)
                    ->color($this->diffColor($part->kind));
                continue;
            }
            $letters[] = UI::text($part->text)
                ->preserveWhitespace()
                ->fontSize(FontSize::Small)
                ->color($this->diffColor($part->kind));
        }

        return UI::column()
            ->gap(Unit::px(1))
            ->content(
                $name,
                // Same size as the name above it: the two are the pair being
                // compared, and shrinking one made it read as a footnote.
                UI::row()->wrap()->content(...$letters),
            );
    }

    /**
     * Red for the letters they typed that the name does not have (struck
     * through too, where it is rendered), green for the ones they left out,
     * ink for the letters they got.
     */
    private function diffColor(DiffKind $kind): Color
    {
        return match ($kind) {
            DiffKind::Added => Palette::red(),
            DiffKind::Missing => Palette::green(),
            DiffKind::Same => Palette::ink(),
        };
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
                        UI::text('Flags to review — ' . count($this->missed))
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
