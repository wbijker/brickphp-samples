<?php

namespace Samples\FlagQuiz;

use BrickPHP\Events\Key;
use BrickPHP\Js\Dom;
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
 * Server round-trip adaptations: the timer's value comes from a start timestamp
 * on each render — the server stays the authority on how long the game has run
 * — and a small client script ticks the displayed seconds between renders, so
 * the clock keeps moving while nothing is being sent. A correct guess advances
 * on Enter (there is no client-side auto-advance).
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

    /**
     * Pinpoint mode: the country wrongly clicked for the question still on
     * screen, so the map can show where the player went and the prompt can name
     * it. Only ever set while {@see $wrong} is — cleared on every move to a new
     * question, since it belongs to the question that was missed, not the next.
     */
    private string $pickedIso = '';

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
    /**
     * The last wrong thing typed at each order position, keyed by position —
     * only positions that actually got a wrong guess appear. Kept so the
     * results screen can show the player what they said. In lenient mode a
     * flag can be guessed at several times; the latest wrong one wins, being
     * where they were when they gave up.
     *
     * @var string[]
     */
    private array $wrongGuesses = [];
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
        $this->useState($this->pickedIso);
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
        $this->useState($this->wrongGuesses);
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
        $this->beginRound($this->selectedCountryIndexes());
    }

    /**
     * Play the flags this game got wrong, and nothing else — the shortest
     * route from "here is what you missed" to practising exactly that. The
     * settings, mode and ordering all carry over; only the set narrows.
     */
    private function retryMissed(): void
    {
        $missed = $this->missedFlags();
        if ($missed === []) {
            return;
        }
        // Resolved against the current order before beginRound() replaces it.
        $this->beginRound(array_map(fn(MissedFlag $m) => $this->order[$m->pos], $missed));
    }

    /**
     * Start a round over these countries: order them, clear every per-game
     * counter, restart the clock. The one place a game begins, so a retry
     * can't drift from a fresh start.
     *
     * @param int[] $indexes indices into {@see Country::all()}
     */
    private function beginRound(array $indexes): void
    {
        // Always shuffle first; for the grouped orderings a stable sort by the
        // similarity key (stable since PHP 8.0) then clusters lookalikes while
        // keeping their within-group order random.
        shuffle($indexes);
        if ($this->flagSort !== FlagSort::Random) {
            $all = Country::all();
            usort(
                $indexes,
                fn(int $a, int $b) => $this->flagSort->keyFor($all[$a]) <=> $this->flagSort->keyFor($all[$b]),
            );
        }
        $this->order = $indexes;
        $this->index = 0;
        $this->status = array_fill(0, count($indexes), Answer::Pending);
        $this->wrong = false;
        $this->pickedIso = '';
        $this->history = [];
        $this->wrongGuesses = [];
        $this->elapsed = 0;
        $this->startTime = time();
        $this->phase = GamePhase::Playing;
        // Put the cursor in the answer field so the first flag can be typed
        // straight away. The field's own autofocus() cannot do this: the
        // browser only flushes autofocus candidates while the document loads,
        // and this field is inserted by a patch long after that — so the
        // attribute is silently ignored and focus stays on the Start button.
        // Pinpoint is answered on the map and has no field to focus.
        if (!$this->mode->answersByClick()) {
            Dom::focus('fq-input');
        }
    }

    /**
     * The flags actually got wrong or given up on — not the ones never
     * reached, which are still pending when a game is finished early. Each
     * carries whatever the player last typed for it, so the results screen
     * and a retry both read from one walk of the order.
     *
     * @return MissedFlag[]
     */
    private function missedFlags(): array
    {
        $all = Country::all();
        $missed = [];
        foreach ($this->order as $pos => $countryIdx) {
            $status = $this->status[$pos] ?? Answer::Pending;
            if ($status === Answer::Wrong || $status === Answer::Skipped) {
                $missed[] = new MissedFlag(
                    $pos,
                    $all[$countryIdx],
                    $this->wrongGuesses[$pos] ?? '',
                    $this->mode->answersByClick() ? GuessKind::Picked : GuessKind::Typed,
                );
            }
        }
        return $missed;
    }

    /**
     * Handle a submitted guess. A correct guess always advances. A wrong guess
     * is final (marked and advanced) in strict mode, otherwise it just flags
     * the input red so the player can retry.
     */
    private function handleGuess(string $value): void
    {
        $correct = $this->current()->matches($value);
        // Recorded before judging, which may advance past this position.
        if (!$correct && trim($value) !== '') {
            $this->wrongGuesses[$this->index] = trim($value);
        }
        $this->judge($correct);
    }

    /**
     * A clicked country on the quiz map.
     *
     * In Pinpoint the click is the answer — that is the whole mode, and the
     * question (a flag and a name) gives nothing away about where the click
     * should land. Everywhere else it is navigation only: the map is something
     * you drag, zoom and poke at, and the browser calls the end of any of that
     * a click, so in the modes that are answered by typing nothing that casual
     * is allowed to spend an answer.
     *
     * As navigation it moves the question rather than answering it: with "free
     * navigation" on, clicking an unanswered country makes it the target; with
     * it off the target is fixed and a click does nothing at all.
     */
    private function handlePick(string $iso): void
    {
        $iso = strtolower($iso);
        if ($this->mode->answersByClick()) {
            $this->judgePick($iso);
            return;
        }
        if (!$this->showFlags) {
            return;
        }
        $pos = $this->posForIso($iso);
        if ($pos !== null && ($this->status[$pos] ?? Answer::Pending) === Answer::Pending) {
            $this->index = $pos;
            $this->wrong = false;
            Dom::focus('fq-input');
        }
    }

    /**
     * Pinpoint: judge a clicked country against the flag on show. A miss is
     * remembered by name — the country they landed on — which is what the
     * results screen reads back and, until the question changes, what the map
     * paints red.
     */
    private function judgePick(string $iso): void
    {
        $correct = $iso === $this->current()->code;
        if (!$correct) {
            $picked = Country::byCode($iso);
            // Recorded before judging, which may advance past this position.
            $this->wrongGuesses[$this->index] = $picked?->name ?? '';
            $this->pickedIso = $iso;
        }
        $this->judge($correct);
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
        // The wrong pick belonged to the question being left behind.
        $this->pickedIso = '';
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
        $this->pickedIso = '';
        // Jumping moved focus to the clicked flag — return it to the input
        // (runs after the DOM patch is applied).
        Dom::focus('fq-input');
    }

    private function current(): Country
    {
        return Country::all()[$this->order[$this->index] ?? 0];
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
                    $this->phase === GamePhase::Playing && $this->mode === GameMode::Pinpoint => $this->buildPlayPinpoint($total, $answered),
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
        $time = Duration::since($this->startTime);

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

    private function buildLeftPanel(int $answered, int $total, int $score, int $right, int $wrong, Duration $time, bool $showGrid): UIElement
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
        $time = Duration::since($this->startTime);
        $right = $this->countHistory(Answer::Correct);
        $wrong = $this->countHistory(Answer::Wrong);

        $greens = $this->isosAnswered(Answer::Correct);
        $reds = $this->isosAnswered(Answer::Wrong);
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
                                : '· type its name')
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

    /**
     * Pinpoint mode: the flag and its name above a blank world map, and the
     * answer is where you click. Nothing on the map is highlighted and it never
     * auto-zooms — either would give the question away — so the map holds still
     * and only fills in behind you as countries are answered.
     */
    private function buildPlayPinpoint(int $total, int $answered): UIElement
    {
        $score = $answered > 0 ? (int)round($this->countStatus(Answer::Correct) / $answered * 100) : 0;
        $time = Duration::since($this->startTime);
        $right = $this->countHistory(Answer::Correct);
        $wrong = $this->countHistory(Answer::Wrong);

        $greens = $this->isosAnswered(Answer::Correct);
        $reds = $this->isosAnswered(Answer::Wrong);
        // A lenient miss leaves the country actually clicked showing red until
        // the next try — where the guess went is the correction.
        if ($this->wrong && $this->pickedIso !== '') {
            $reds[] = $this->pickedIso;
        }

        // Fullscreen: the map fills the viewport edge-to-edge — no card chrome
        // (margins / border / rounding / shadow) boxing it in.
        return UI::column()
            ->grow()
            ->minHeight(Unit::em(0))
            ->background(Palette::white())
            ->clipContent()
            // Escape passes on the flag, as it does in the typed modes. There
            // it hangs off the answer field; here there is no field, so the
            // screen itself carries the key.
            ->onGlobalKeyDown(fn() => $this->next(), [Key::Escape], preventDefault: true)
            ->content(
                new ScoreBar($answered, $total, $score, $right, $wrong, $time, array_slice($this->history, -5)),
                $this->buildPinpointPrompt(),
                UI::column()
                    ->grow()
                    ->minHeight(Unit::em(0))
                    ->content(
                        new WorldMap(
                            '',
                            $greens,
                            $reds,
                            fn(string $iso) => $this->handlePick($iso),
                            autoZoom: false,
                        ),
                    ),
                $this->buildPinpointHint(),
                new BrandInfo(fn() => $this->phase = GamePhase::Start, fn() => $this->finish()),
            );
    }

    /**
     * The Pinpoint question: the flag, its name, and how the answer is going —
     * asking for the click, or naming the country the last one landed on.
     */
    private function buildPinpointPrompt(): UIElement
    {
        $country = $this->current();
        $picked = $this->pickedIso !== '' ? Country::byCode($this->pickedIso) : null;

        return UI::row()
            ->noShrink()
            ->wrap()
            ->alignMiddle()
            ->gap(Unit::px(14))
            ->bordered(bottom: 1)
            ->borderColor($this->wrong ? Palette::red() : Palette::border())
            ->background($this->wrong ? Palette::redWash() : Palette::white())
            ->padding(x: Unit::px(16), y: Unit::px(12))
            ->padding(x: Unit::px(24), pseudo: Pseudo::lg())
            ->content(
                UI::image($country->thumbUrl(), $country->name)
                    ->noShrink()
                    ->width(Unit::px(60))
                    ->height(Unit::px(40))
                    ->objectContain()
                    ->rounded(Unit::px(4))
                    ->bordered()
                    ->borderColor(Palette::border())
                    ->shadow(Shadow::Small),
                UI::column()
                    ->gap(Unit::px(2))
                    ->content(
                        UI::text($country->name)
                            ->weight(FontWeight::SemiBold)
                            ->fontSize(FontSize::Large)
                            ->fontSize(FontSize::TwoXL, Pseudo::sm()),
                        $picked !== null
                            ? UI::text('That is ' . $picked->name . ' — try again')
                                ->fontSize(FontSize::Small)->weight(FontWeight::Medium)->color(Palette::red())
                            : UI::text('Click this country on the map')
                                ->fontSize(FontSize::Small)->color(Palette::subtle()),
                    ),
            );
    }

    /** The Pinpoint footer: how to answer, and the way out of a flag. */
    private function buildPinpointHint(): UIElement
    {
        return UI::row()
            ->noShrink()
            ->alignMiddle()
            ->gap(Unit::px(8))
            ->bordered(top: 1)
            ->borderColor(Palette::border())
            ->padding(x: Unit::px(22), y: Unit::px(14))
            ->fontSize(FontSize::Small)
            ->color(Palette::subtle())
            ->content(
                UI::text('Click the country · Esc to pass'),
                UI::text('·')->color(Palette::dot()),
                UI::button('Skip')
                    ->borderNone()
                    ->background(Palette::transparent())
                    ->color(Palette::blue())
                    ->weight(FontWeight::SemiBold)
                    ->padding(Unit::none())
                    ->clickable()
                    ->onClick(fn() => $this->skip()),
            );
    }

    /**
     * The ISO-2 codes of every question decided this way — what the map paints
     * green and red.
     *
     * @return string[]
     */
    private function isosAnswered(Answer $answer): array
    {
        $all = Country::all();
        $out = [];
        foreach ($this->order as $pos => $countryIdx) {
            if (($this->status[$pos] ?? Answer::Pending) === $answer) {
                $out[] = $all[$countryIdx]->code;
            }
        }
        return $out;
    }

    /**
     * Explore mode: a country list on the left, the world map on the right —
     * with every country wearing its own flag, so the map itself teaches the
     * flag/place pairing the quiz modes then test.
     */
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
        $correct = $this->countStatus(Answer::Correct);
        $answered = $this->answeredCount();
        // Accuracy over what was actually attempted (so an early "Done" isn't
        // diluted by flags never reached).
        $accuracy = $answered > 0 ? (int)round($correct / $answered * 100) : 0;
        // Score is over every flag in the game, so calling it early counts the
        // ones never reached against you — the difference from accuracy, which
        // only weighs what was attempted.
        $score = $total > 0 ? (int)round($correct / $total * 100) : 0;

        return new FinishedScreen(
            $correct,
            $total,
            $accuracy,
            $score,
            (new Duration($this->elapsed))->clock(),
            $this->missedFlags(),
            fn() => $this->startGame(),
            fn() => $this->phase = GamePhase::Start,
            fn() => $this->retryMissed(),
        );
    }
}
