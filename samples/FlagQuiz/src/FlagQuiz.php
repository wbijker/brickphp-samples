<?php

namespace Samples\FlagQuiz;

use BrickPHP\Js\Js;
use BrickPHP\UI\Direction;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use Samples\FlagQuiz\Components\BrandInfo;
use Samples\FlagQuiz\Components\ChipToggle;
use Samples\FlagQuiz\Components\CountryList;
use Samples\FlagQuiz\Components\FlagGrid;
use Samples\FlagQuiz\Components\GuessInput;
use Samples\FlagQuiz\Components\GuessPanel;
use Samples\FlagQuiz\Components\ScoreBar;
use Samples\FlagQuiz\Components\WorldMap;
use Samples\FlagQuiz\Screens\FinishedScreen;
use Samples\FlagQuiz\Screens\StartScreen;

/**
 * Vexi — the game's stateful core. Holds the session state and the rules,
 * and composes the screens/components ({@see StartScreen}, {@see ScoreBar},
 * {@see GuessPanel}, {@see FlagGrid}, {@see FinishedScreen}) that render it.
 * Country data lives in {@see Country}, colours in {@see Palette}. The phase,
 * mode and per-question outcomes are typed enums ({@see GamePhase},
 * {@see GameMode}, {@see Answer}) rather than magic strings.
 *
 * Layout is responsive: one block that stacks vertically on narrow screens and
 * splits left/right from the `lg` breakpoint up. The page scrolls on small
 * screens; on `lg`+ the shell is a fixed viewport and the flag grid scrolls
 * inside it.
 *
 * Server round-trip adaptations: the timer is derived from a start timestamp on
 * each render rather than ticking client-side, and a correct guess advances on
 * Enter (there is no client-side auto-advance).
 */
class FlagQuiz extends Component
{
    private GamePhase $phase = GamePhase::Start;

    /** The mode currently being played (drives routing). */
    private GameMode $mode = GameMode::Flags;

    /**
     * The quiz mode selected on the start screen (Flags or Location only).
     * Explore is launched separately via its link, so it never lands here —
     * keeping the start screen's card selection and settings always valid.
     */
    private GameMode $quizMode = GameMode::Flags;

    /** Explore mode: ISO-2 of the country currently focused on the map. */
    private string $exploreIso = '';

    /** Map modes: auto-zoom to the highlighted / selected country. */
    private bool $autoZoom = true;

    /** Settings, chosen on the start screen and kept across games. */
    private bool $showFlags = true;
    private bool $strict = true;

    /** How flags are ordered: Random, or grouped so lookalikes sit together. */
    private FlagSort $flagSort = FlagSort::Random;

    /** @var Continent[] continents a game is restricted to (all by default) */
    private array $continents = [];

    /** @var int[] shuffled indices into Country::all() */
    private array $order = [];
    private int $index = 0;
    /** @var Answer[] one entry per order position (Pending until decided) */
    private array $status = [];
    private bool $wrong = false;
    /**
     * One entry per *decided* flag, in order. Only a correct answer or a
     * strict-mode wrong answer (where we move on) records an entry — a
     * retryable wrong guess does not. Holds only Answer::Correct / Answer::Wrong.
     *
     * @var Answer[]
     */
    private array $history = [];
    private int $startTime = 0;
    private int $elapsed = 0;

    protected function initialize(): void
    {
        // Default: every continent selected. Set before useState so a restored
        // session subset (always non-empty) overrides this default.
        if ($this->continents === []) {
            $this->continents = Continent::cases();
        }

        $this->useState($this->phase);
        $this->useState($this->mode);
        $this->useState($this->quizMode);
        $this->useState($this->exploreIso);
        $this->useState($this->autoZoom);
        $this->useState($this->showFlags);
        $this->useState($this->strict);
        $this->useState($this->flagSort);
        $this->useState($this->continents);
        $this->useState($this->order);
        $this->useState($this->index);
        $this->useState($this->status);
        $this->useState($this->wrong);
        $this->useState($this->history);
        $this->useState($this->startTime);
        $this->useState($this->elapsed);
    }

    // ============================================================
    // Game logic
    // ============================================================

    /** Start the selected quiz mode (Flags or Location). */
    private function startQuiz(): void
    {
        $this->mode = $this->quizMode;
        $this->startGame();
    }

    /** Jump straight into free Explore mode (its own single-click link). */
    private function startExplore(): void
    {
        $this->mode = GameMode::Explore;
        $this->startGame();
    }

    private function startGame(): void
    {
        if ($this->mode === GameMode::Explore) {
            // Free exploration — no quiz state.
            $this->exploreIso = '';
            $this->phase = GamePhase::Playing;
            return;
        }

        // Restrict the game to the chosen continents.
        $this->order = $this->selectedCountryIndexes();
        // Always shuffle first; for the grouped orderings a stable sort by the
        // similarity key (stable since PHP 8.0) then clusters lookalikes while
        // keeping their within-group order random.
        shuffle($this->order);
        if ($this->flagSort !== FlagSort::Random) {
            $all = Country::all();
            usort(
                $this->order,
                fn(int $a, int $b) => $this->flagSort->keyFor($all[$a]) <=> $this->flagSort->keyFor($all[$b]),
            );
        }
        $n = count($this->order);
        $this->index = 0;
        $this->status = array_fill(0, $n, Answer::Pending);
        $this->wrong = false;
        $this->history = [];
        $this->elapsed = 0;
        $this->startTime = time();
        $this->phase = GamePhase::Playing;
    }

    /**
     * Handle a submitted guess. A correct guess always advances. A wrong guess
     * is final (marked and advanced) in strict mode, otherwise it just flags
     * the input red so the player can retry.
     */
    private function handleGuess(string $value): void
    {
        $this->judge($this->current()->matches($value));
    }

    /**
     * A clicked country in Locations mode. When "free navigation" is on you
     * can move between countries — clicking an unanswered one selects it as the
     * target. When off, you can't move, so a click is a guess of the current
     * target instead.
     *
     * In strict mode a wrong answer is final, so an errant map click would
     * permanently mark the country wrong and advance. To avoid that, map clicks
     * are discarded entirely in strict mode and do nothing.
     */
    private function handlePick(string $iso): void
    {
        if ($this->strict) {
            return;
        }
        $iso = strtolower($iso);
        if (!$this->showFlags) {
            $this->judge($iso === $this->current()->code);
            return;
        }
        $pos = $this->posForIso($iso);
        if ($pos !== null && ($this->status[$pos] ?? Answer::Pending) === Answer::Pending) {
            $this->index = $pos;
            $this->wrong = false;
            Js::run("var i=document.getElementById('fq-input'); if (i) { i.focus(); }");
        }
    }

    /** Explore mode: focus the map on the clicked / chosen country. */
    private function exploreSelect(string $iso): void
    {
        $this->exploreIso = strtolower($iso);
    }

    /** Order position of the country with this ISO-2 code, or null. */
    private function posForIso(string $iso): ?int
    {
        $all = Country::all();
        foreach ($this->order as $pos => $countryIdx) {
            if ($all[$countryIdx]->code === $iso) {
                return $pos;
            }
        }
        return null;
    }

    /**
     * Resolve a guess. Correct always advances. A wrong guess is final (marked
     * and advanced) in strict mode, otherwise it just flags the input red so
     * the player can retry.
     */
    private function judge(bool $correct): void
    {
        if ($correct) {
            $this->status[$this->index] = Answer::Correct;
            $this->wrong = false;
            $this->history[] = Answer::Correct;
            $this->advance();
        } elseif ($this->strict) {
            $this->status[$this->index] = Answer::Wrong;
            $this->wrong = false;
            $this->history[] = Answer::Wrong;
            $this->advance();
        } else {
            $this->wrong = true;
        }
    }

    private function skip(): void
    {
        $this->status[$this->index] = Answer::Skipped;
        $this->wrong = false;
        $this->advance();
    }

    /** Pass: move to the next flag without marking the current one. */
    private function next(): void
    {
        $this->wrong = false;
        $this->advance();
    }

    /** End the game now — show the results screen, exactly as finishing all flags. */
    private function finish(): void
    {
        $this->elapsed = time() - $this->startTime;
        $this->wrong = false;
        $this->phase = GamePhase::Finished;
    }

    private function toggleShowFlags(): void
    {
        $this->showFlags = !$this->showFlags;
    }

    private function toggleStrict(): void
    {
        $this->strict = !$this->strict;
    }

    private function setFlagSort(FlagSort $sort): void
    {
        $this->flagSort = $sort;
    }

    /**
     * Switch the selected quiz mode. The order options differ per mode, so if
     * the current choice isn't offered for the new mode, fall back to Random.
     */
    private function setQuizMode(GameMode $mode): void
    {
        $this->quizMode = $mode;
        if (!in_array($this->flagSort, FlagSort::forMode($mode), true)) {
            $this->flagSort = FlagSort::Random;
        }
    }

    private function toggleAutoZoom(): void
    {
        $this->autoZoom = !$this->autoZoom;
    }

    /** Add/remove a continent from the selection (never empties the set). */
    private function toggleContinent(Continent $continent): void
    {
        if (in_array($continent, $this->continents, true)) {
            if (count($this->continents) <= 1) {
                return; // keep at least one continent in play
            }
            $this->continents = array_values(
                array_filter($this->continents, fn(Continent $c) => $c !== $continent),
            );
        } else {
            $this->continents[] = $continent;
        }
    }

    /** @return int[] indices into Country::all() within the selected continents */
    private function selectedCountryIndexes(): array
    {
        $out = [];
        foreach (Country::all() as $i => $country) {
            if (in_array($country->continent, $this->continents, true)) {
                $out[] = $i;
            }
        }
        return $out;
    }

    private function advance(): void
    {
        $n = count($this->order);
        for ($k = 1; $k <= $n; $k++) {
            $j = ($this->index + $k) % $n;
            if (($this->status[$j] ?? Answer::Pending) === Answer::Pending) {
                $this->index = $j;
                return;
            }
        }
        $this->elapsed = time() - $this->startTime;
        $this->wrong = false;
        $this->phase = GamePhase::Finished;
    }

    private function jumpTo(int $pos): void
    {
        if (($this->status[$pos] ?? Answer::Pending) !== Answer::Pending) {
            return;
        }
        $this->index = $pos;
        $this->wrong = false;
        // Jumping moved focus to the clicked flag — return it to the input
        // (runs after the DOM patch is applied).
        Js::run("var i=document.getElementById('fq-input'); if (i) { i.focus(); }");
    }

    private function current(): Country
    {
        return Country::all()[$this->order[$this->index] ?? 0];
    }

    private function fmtTime(int $seconds): string
    {
        return intdiv($seconds, 60) . ':' . str_pad((string)($seconds % 60), 2, '0', STR_PAD_LEFT);
    }

    /** How many order positions have been decided (not still pending). */
    private function answeredCount(): int
    {
        return count(array_filter($this->status, fn(Answer $a) => $a->isDecided()));
    }

    /** How many order positions carry a given outcome. */
    private function countStatus(Answer $answer): int
    {
        return count(array_filter($this->status, fn(Answer $a) => $a === $answer));
    }

    /** How many history entries carry a given outcome. */
    private function countHistory(Answer $answer): int
    {
        return count(array_filter($this->history, fn(Answer $a) => $a === $answer));
    }

    // ============================================================
    // Render
    // ============================================================

    protected function build(): VNode
    {
        // During a game the total is the filtered set in play (the shuffled
        // order); on the start screen it's how many the current selection holds.
        $total = $this->phase === GamePhase::Start
            ? count($this->selectedCountryIndexes())
            : count($this->order);
        $answered = $this->answeredCount();
        $isExplore = $this->phase === GamePhase::Playing && $this->mode === GameMode::Explore;

        return UI::column()
            ->minHeight(Unit::vh(100))
            ->height(Unit::vh(100), Pseudo::lg())
            ->clipContent(Pseudo::lg())
            ->width(Unit::full())
            ->background(Palette::page())
            ->color(Palette::ink())
            ->content(
                match (true) {
                    $this->phase === GamePhase::Finished => $this->buildFinished($total),
                    $isExplore => $this->buildExplore(),
                    $this->phase === GamePhase::Playing && $this->mode === GameMode::Location => $this->buildPlayLocation($total, $answered),
                    $this->phase === GamePhase::Playing => $this->buildPlay($total, $answered),
                    default => new StartScreen(
                        $total,
                        $this->quizMode,
                        $this->showFlags,
                        $this->strict,
                        $this->flagSort,
                        $this->continents,
                        fn() => $this->startQuiz(),
                        fn(GameMode $mode) => $this->setQuizMode($mode),
                        fn() => $this->toggleShowFlags(),
                        fn() => $this->toggleStrict(),
                        fn(FlagSort $s) => $this->setFlagSort($s),
                        fn(Continent $c) => $this->toggleContinent($c),
                        fn() => $this->startExplore(),
                    ),
                }
            );
    }

    private function buildPlay(int $total, int $answered): UIElement
    {
        $score = $answered > 0 ? (int)round($this->countStatus(Answer::Correct) / $answered * 100) : 0;
        $time = $this->fmtTime(time() - $this->startTime);

        $right = $this->countHistory(Answer::Correct);
        $wrong = $this->countHistory(Answer::Wrong);

        $remaining = $this->remainingItems();
        $showGrid = $this->showFlags && count($remaining) > 0;

        // One off-white block: stacks vertically on small screens, splits
        // left/right at lg. The left panel is white; the flag grid shows the
        // block's off-white through.
        return UI::column()
            ->grow()
            ->minHeight(Unit::em(0))
            ->direction(Direction::row(), Pseudo::lg())
            ->margin(Unit::px(16))
            ->margin(Unit::px(32), Pseudo::lg())
            ->background(Palette::offWhite())
            ->bordered()
            ->borderColor(Palette::border())
            ->rounded(Unit::px(18))
            ->shadow(Shadow::Large)
            ->clipContent()
            ->content(
                $this->buildLeftPanel($answered, $total, $score, $right, $wrong, $time, $showGrid),
                ...($showGrid ? [new FlagGrid($remaining, $this->index, fn(int $pos) => $this->jumpTo($pos))] : []),
            );
    }

    private function buildLeftPanel(int $answered, int $total, int $score, int $right, int $wrong, string $time, bool $showGrid): UIElement
    {
        $children = [
            new ScoreBar($answered, $total, $score, $right, $wrong, $time, array_slice($this->history, -5)),
            new GuessPanel(
                $this->current(),
                $this->wrong,
                fn(string $value) => $this->handleGuess($value),
                fn() => $this->skip(),
                fn() => $this->next(),
            ),
            new BrandInfo(fn() => $this->phase = GamePhase::Start, fn() => $this->finish()),
        ];

        // Built as one fluent chain per branch so the CssExtractor harvests the
        // width/border classes (chaining onto a stored variable is not scanned).
        if ($showGrid) {
            // Fixed sidebar at lg, full-width stacked below.
            return UI::column()
                ->background(Palette::white())
                ->width(Unit::full())
                ->width(Unit::px(720), Pseudo::lg())
                ->noShrink(Pseudo::lg())
                ->bordered(bottom: 1)
                ->bordered(right: 1, pseudo: Pseudo::lg())
                ->borderColor(Palette::border())
                ->content(...$children);
        }

        // No grid: centre the panel inside the block.
        return UI::column()
            ->background(Palette::white())
            ->width(Unit::full())
            ->maxWidth(Unit::px(760))
            ->marginX(Unit::auto())
            ->content(...$children);
    }

    /** Locations mode: scorebar, the world map (target highlighted), the input. */
    private function buildPlayLocation(int $total, int $answered): UIElement
    {
        $score = $answered > 0 ? (int)round($this->countStatus(Answer::Correct) / $answered * 100) : 0;
        $time = $this->fmtTime(time() - $this->startTime);
        $right = $this->countHistory(Answer::Correct);
        $wrong = $this->countHistory(Answer::Wrong);

        $all = Country::all();
        $greens = [];
        $reds = [];
        foreach ($this->order as $pos => $countryIdx) {
            $status = $this->status[$pos] ?? Answer::Pending;
            if ($status === Answer::Correct) {
                $greens[] = $all[$countryIdx]->code;
            } elseif ($status === Answer::Wrong) {
                $reds[] = $all[$countryIdx]->code;
            }
        }
        if ($this->wrong) {
            // A lenient miss flashes the target red until the next try.
            $reds[] = $this->current()->code;
        }

        // Fullscreen: the map fills the viewport edge-to-edge — no card chrome
        // (margins / border / rounding / shadow) boxing it in.
        return UI::column()
            ->grow()
            ->minHeight(Unit::em(0))
            ->background(Palette::white())
            ->clipContent()
            ->content(
                new ScoreBar($answered, $total, $score, $right, $wrong, $time, array_slice($this->history, -5)),
                // Prompt + auto-zoom control. Wraps so the chip drops below the
                // prompt on narrow screens instead of crowding it.
                UI::row()
                    ->noShrink()
                    ->wrap()
                    ->alignMiddle()
                    ->alignBetween()
                    ->gap(Unit::px(10))
                    ->bordered(bottom: 1)
                    ->borderColor(Palette::border())
                    ->padding(x: Unit::px(16), y: Unit::px(12))
                    ->padding(x: Unit::px(24), pseudo: Pseudo::lg())
                    ->content(
                        UI::row()->wrap()->alignMiddle()->gap(Unit::px(8))->content(
                            UI::text('Which country is highlighted?')
                                ->fontSize(FontSize::Small)->weight(FontWeight::SemiBold),
                            UI::text($this->showFlags
                                ? '· type its name · tap any country to jump there'
                                : '· tap it on the map or type its name')
                                ->fontSize(FontSize::Small)->color(Palette::subtle()),
                        ),
                        new ChipToggle('Auto-zoom', $this->autoZoom, fn() => $this->toggleAutoZoom()),
                    ),
                UI::column()
                    ->grow()
                    ->minHeight(Unit::em(0))
                    ->content(
                        new WorldMap(
                            $this->current()->code,
                            $greens,
                            $reds,
                            fn(string $iso) => $this->handlePick($iso),
                            autoZoom: $this->autoZoom,
                        ),
                    ),
                new GuessInput(
                    $this->current()->code,
                    $this->wrong,
                    fn(string $value) => $this->handleGuess($value),
                    fn() => $this->skip(),
                    fn() => $this->next(),
                ),
                new BrandInfo(fn() => $this->phase = GamePhase::Start, fn() => $this->finish()),
            );
    }

    /** Explore mode: a country list on the left, the world map on the right. */
    private function buildExplore(): UIElement
    {
        $selected = $this->exploreIso !== '' ? Country::byCode($this->exploreIso) : null;

        // Fullscreen: the map fills the viewport edge-to-edge — no card chrome
        // (margins / border / rounding / shadow) boxing it in.
        return UI::column()
            ->grow()
            ->minHeight(Unit::em(0))
            ->background(Palette::white())
            ->clipContent()
            ->content(
                // Header: title + the focused country's flag & name + Back.
                // Wraps so the controls fall below the title on small screens.
                UI::row()
                    ->noShrink()
                    ->wrap()
                    ->alignMiddle()
                    ->alignBetween()
                    ->gap(Unit::px(12))
                    ->bordered(bottom: 1)
                    ->borderColor(Palette::border())
                    ->padding(x: Unit::px(16), y: Unit::px(12))
                    ->padding(x: Unit::px(20), pseudo: Pseudo::lg())
                    ->content(
                        $selected !== null
                            ? UI::row()->alignMiddle()->gap(Unit::px(11))->content(
                                UI::image($selected->thumbUrl(), '')
                                    ->width(Unit::px(40))->height(Unit::px(27))->objectContain()
                                    ->rounded(Unit::px(4))->bordered()->borderColor(Palette::border()),
                                UI::text($selected->name)->weight(FontWeight::SemiBold)->fontSize(FontSize::Large),
                            )
                            : UI::text('Explore the world — pick a country to focus the map')
                                ->fontSize(FontSize::Small)->color(Palette::subtle()),
                        UI::row()->noShrink()->alignMiddle()->gap(Unit::px(20))->content(
                            new ChipToggle('Auto-zoom', $this->autoZoom, fn() => $this->toggleAutoZoom()),
                            UI::button('Back to start')
                                ->noShrink()
                                ->borderNone()
                                ->background(Palette::transparent())
                                ->color(Palette::blue())
                                ->weight(FontWeight::SemiBold)
                                ->fontSize(FontSize::Small)
                                ->padding(Unit::none())
                                ->clickable()
                                ->onClick(fn() => $this->phase = GamePhase::Start),
                        ),
                    ),
                // Body: list (left) + map (right); stacks on small screens.
                UI::column()
                    ->grow()
                    ->minHeight(Unit::em(0))
                    ->direction(Direction::row(), Pseudo::lg())
                    ->content(
                        new CountryList(
                            array_values(array_filter(
                                Country::all(),
                                fn(Country $c) => in_array($c->continent, $this->continents, true),
                            )),
                            $this->exploreIso,
                            fn(string $iso) => $this->exploreSelect($iso),
                        ),
                        UI::column()
                            ->grow()
                            ->minHeight(Unit::em(0))
                            ->content(
                                new WorldMap(
                                    $this->exploreIso,
                                    [],
                                    [],
                                    fn(string $iso) => $this->exploreSelect($iso),
                                    labels: true,
                                    autoZoom: $this->autoZoom,
                                ),
                            ),
                    ),
            );
    }

    /** @return RemainingFlag[] all unanswered questions (incl. the current one) */
    private function remainingItems(): array
    {
        $all = Country::all();
        $out = [];
        foreach ($this->order as $pos => $countryIdx) {
            if (($this->status[$pos] ?? Answer::Pending) === Answer::Pending) {
                $out[] = new RemainingFlag($pos, $all[$countryIdx]);
            }
        }
        return $out;
    }

    private function buildFinished(int $total): VNode
    {
        $all = Country::all();
        $correct = $this->countStatus(Answer::Correct);
        $answered = $this->answeredCount();
        // Accuracy over what was actually attempted (so an early "Done" isn't
        // diluted by flags never reached).
        $accuracy = $answered > 0 ? (int)round($correct / $answered * 100) : 0;

        // The flags actually got wrong or gave up on — not the ones never
        // reached (still pending when finishing early).
        $missed = [];
        foreach ($this->order as $pos => $countryIdx) {
            $status = $this->status[$pos] ?? Answer::Pending;
            if ($status === Answer::Wrong || $status === Answer::Skipped) {
                $missed[] = $all[$countryIdx];
            }
        }

        return new FinishedScreen(
            $correct,
            $total,
            $accuracy,
            $this->fmtTime($this->elapsed),
            $missed,
            fn() => $this->startGame(),
            fn() => $this->phase = GamePhase::Start,
        );
    }
}
