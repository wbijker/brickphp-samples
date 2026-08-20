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
use Samples\FlagQuiz\Components\FlagChoices;
use Samples\FlagQuiz\Components\FlagGrid;
use Samples\FlagQuiz\Components\GuessInput;
use Samples\FlagQuiz\Components\ScoreBar;
use Samples\FlagQuiz\Components\WorldMap;
use Samples\FlagQuiz\Screens\FinishedScreen;
use Samples\FlagQuiz\Screens\StartScreen;

/**
 * Vexi — the game's stateful core. Holds the session state and the rules,
 * and composes the screens/components ({@see StartScreen}, {@see ScoreBar},
 * {@see FlagChoices}, {@see FlagGrid}, {@see FinishedScreen}) that render it.
 * Country data lives in {@see Country}, colours in {@see Palette}. The phase,
 * mode and per-question outcomes are typed enums ({@see GamePhase},
 * {@see GameMode}, {@see Answer}) rather than magic strings.
 *
 * A game is a {@see Quiz}: some of a country's four {@see Attribute}s shown,
 * and one of them asked for. That pairing decides everything downstream — how
 * the question is drawn, whether the map is the question or the answer, what
 * an answer even is — so the screens below ask it rather than testing for
 * named modes.
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
    /** How many flags are offered when the flag is the thing being asked for. */
    private const FLAG_CHOICES = 6;

    private GamePhase $phase = GamePhase::Start;

    /** Whether a quiz is being played or the map is being browsed. */
    private GameMode $mode = GameMode::Quiz;

    /**
     * The question being asked, as chosen on the start screen: the attributes
     * shown, and the one asked for. The destination is never also a source —
     * {@see setDestination()} keeps that true — so the pair always describes a
     * question that can be answered. Held as two pieces of state rather than a
     * {@see Quiz} because state is what a session stores; the pairing itself is
     * built from them per render.
     *
     * @var Attribute[]
     */
    private array $sources = [Attribute::Flag];
    private Attribute $destination = Attribute::Name;

    /** Explore mode: ISO-2 of the country currently focused on the map. */
    private string $exploreIso = '';

    /**
     * The country wrongly pointed at for the question still on screen, so the
     * map or the flag cell can show where the player went and the prompt can
     * name it. Only ever set while {@see $wrong} is — cleared on every move to a new
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
        $this->useState($this->sources);
        $this->useState($this->destination);
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

    /** The question this game is asking — built from state, never stored. */
    private function quiz(): Quiz
    {
        return new Quiz($this->sources, $this->destination);
    }

    /** Start a quiz on the chosen pairing. */
    private function startQuiz(): void
    {
        $this->mode = GameMode::Quiz;
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
        // Only the typed questions have a field to focus; the rest are
        // answered by pointing at something.
        if ($this->quiz()->isTyped()) {
            Dom::focus('fq-input');
        }
    }

    /**
     * The questions actually got wrong or given up on — not the ones never
     * reached, which are still pending when a game is finished early. Each
     * carries whatever the player last answered, so the results screen and a
     * retry both read from one walk of the order.
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
                $missed[] = new MissedFlag($pos, $all[$countryIdx], $this->wrongGuesses[$pos] ?? '');
            }
        }
        return $missed;
    }

    /**
     * A typed answer. Right or wrong is the destination's business — the same
     * field takes a country name or a capital city, and only the attribute
     * being asked for knows which words count.
     *
     * A correct answer always advances. A wrong one is final (marked and
     * advanced) in strict mode, otherwise it just flags the input red so the
     * player can retry.
     */
    private function handleGuess(string $value): void
    {
        $correct = $this->destination->matches($this->current(), $value);
        // Recorded before judging, which may advance past this position.
        if (!$correct && trim($value) !== '') {
            $this->wrongGuesses[$this->index] = trim($value);
        }
        $this->judge($correct);
    }

    /**
     * A clicked country on the quiz map.
     *
     * When the map is what's being asked for, the click is the answer — that
     * is the whole question, and nothing on the map gives away where it should
     * land. When the map is instead part of the question, a click is navigation
     * only: the map is something you drag, zoom and poke at, and the browser
     * calls the end of any of that a click, so where the answer is typed
     * nothing that casual is allowed to spend one.
     *
     * As navigation it moves the question rather than answering it: with "free
     * navigation" on, clicking an unanswered country makes it the target; with
     * it off the target is fixed and a click does nothing at all.
     */
    private function handlePick(string $iso): void
    {
        $iso = strtolower($iso);
        if ($this->quiz()->mapIsAnswer()) {
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
     * An answer given by pointing — a country clicked on the map, or a flag
     * chosen from the handful on offer. Both name a country, so both are judged
     * the same way. A miss is remembered by the name of the country landed on,
     * which is what the results screen reads back and, until the question
     * changes, what the map or the flag cell shows in red.
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

    /**
     * The flags on offer when the flag is what's being asked for: the right one
     * and five others, drawn from the same continent where there are enough of
     * them so the choice is between neighbours.
     *
     * Deterministic, and deliberately so — the options are worked out afresh on
     * every render, and a shuffle would deal a new hand each time the clock
     * ticked. Ordering by a hash of the question and the code gives the same
     * six in the same places until the question changes.
     *
     * @return Country[]
     */
    private function flagChoices(): array
    {
        $answer = $this->current();
        $seed = $this->startTime . ':' . $this->index . ':' . $answer->code;
        $order = fn(string $salt, Country $a, Country $b): int
            => crc32($seed . $salt . $a->code) <=> crc32($seed . $salt . $b->code);

        $pool = array_values(array_filter(
            Country::all(),
            fn(Country $c) => $c->code !== $answer->code && $c->continent === $answer->continent,
        ));
        if (count($pool) < self::FLAG_CHOICES - 1) {
            // Oceania on its own can't fill six; the rest of the world tops it up.
            $pool = array_merge($pool, array_values(array_filter(
                Country::all(),
                fn(Country $c) => $c->code !== $answer->code && $c->continent !== $answer->continent,
            )));
        }
        usort($pool, fn(Country $a, Country $b) => $order('pool', $a, $b));

        $choices = array_slice($pool, 0, self::FLAG_CHOICES - 1);
        $choices[] = $answer;
        // Shuffled again on a different salt, so the answer isn't always last.
        usort($choices, fn(Country $a, Country $b) => $order('place', $a, $b));
        return $choices;
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
     * Show this attribute, or stop showing it. The last one can't be turned
     * off — a question with nothing shown is nothing to go on — and the one
     * being asked for isn't on offer at all.
     */
    private function toggleSource(Attribute $attribute): void
    {
        if ($attribute === $this->destination) {
            return;
        }
        if (in_array($attribute, $this->sources, true)) {
            if (count($this->sources) <= 1) {
                return;
            }
            $this->sources = array_values(
                array_filter($this->sources, fn(Attribute $a) => $a !== $attribute),
            );
        } else {
            $this->sources[] = $attribute;
            $this->sortSources();
        }
        $this->settleFlagSort();
    }

    /**
     * Ask for this attribute instead. It can no longer be one of the things
     * shown, and if it was the only one, the attribute it replaces takes its
     * place on the left — the question simply turns around, which is what
     * asking for what you were just shown means.
     */
    private function setDestination(Attribute $attribute): void
    {
        if ($attribute === $this->destination) {
            return;
        }
        $replaced = $this->destination;
        $this->destination = $attribute;

        $sources = array_values(array_filter($this->sources, fn(Attribute $a) => $a !== $attribute));
        $this->sources = $sources === [] ? [$replaced] : $sources;
        $this->sortSources();
        $this->settleFlagSort();
    }

    /** Keep the shown attributes in the lists' own order, however they were ticked. */
    private function sortSources(): void
    {
        $order = array_flip(array_map(fn(Attribute $a) => $a->value, Attribute::cases()));
        usort($this->sources, fn(Attribute $a, Attribute $b) => $order[$a->value] <=> $order[$b->value]);
    }

    /** The order options differ per pairing; drop a choice the new one doesn't offer. */
    private function settleFlagSort(): void
    {
        if (!in_array($this->flagSort, FlagSort::forQuiz($this->quiz()), true)) {
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
                    // One playing screen for all twelve pairings: the map ones
                    // fill the viewport, the rest sit on a card.
                    $this->phase === GamePhase::Playing && $this->quiz()->usesMap()
                        => $this->buildPlayOnMap($total, $answered),
                    $this->phase === GamePhase::Playing => $this->buildPlayOnCard($total, $answered),
                    default => new StartScreen(
                        $total,
                        $this->quiz(),
                        $this->showFlags,
                        $this->strict,
                        $this->flagSort,
                        $this->continents,
                        fn() => $this->startQuiz(),
                        fn(Attribute $a) => $this->toggleSource($a),
                        fn(Attribute $a) => $this->setDestination($a),
                        fn() => $this->toggleShowFlags(),
                        fn() => $this->toggleStrict(),
                        fn(FlagSort $s) => $this->setFlagSort($s),
                        fn(Continent $c) => $this->toggleContinent($c),
                        fn() => $this->startExplore(),
                    ),
                }
            );
    }

    /** The header of stats, identical whatever is being asked. */
    private function scoreBar(int $total, int $answered): ScoreBar
    {
        $score = $answered > 0 ? (int)round($this->countStatus(Answer::Correct) / $answered * 100) : 0;

        return new ScoreBar(
            $answered,
            $total,
            $score,
            $this->countHistory(Answer::Correct),
            $this->countHistory(Answer::Wrong),
            Duration::since($this->startTime),
            array_slice($this->history, -5),
        );
    }

    /**
     * The playing screen for the pairings with no map in them: the question on
     * a card, and — while the flags are the question and the answer is typed —
     * the deck of remaining flags beside it.
     */
    private function buildPlayOnCard(int $total, int $answered): UIElement
    {
        $quiz = $this->quiz();
        $remaining = $this->remainingItems();
        // The deck is a way of moving between questions, so it needs questions
        // it can show: the flags, and an answer given somewhere other than by
        // clicking one of them.
        $showGrid = $this->showFlags
            && $quiz->isTyped()
            && $quiz->shows(Attribute::Flag)
            && count($remaining) > 0;

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
                $this->buildCardPanel($total, $answered, $showGrid),
                ...($showGrid ? [new FlagGrid($remaining, $this->index, fn(int $pos) => $this->jumpTo($pos))] : []),
            );
    }

    private function buildCardPanel(int $total, int $answered, bool $showGrid): UIElement
    {
        $children = [
            $this->scoreBar($total, $answered),
            $this->buildCardPrompt(),
            ...$this->buildAnswer(),
            new BrandInfo(fn() => $this->phase = GamePhase::Start, fn() => $this->finish()),
        ];

        // Built as one fluent chain per branch: the two panels differ in half
        // their layout, and a shared chain with six conditionals in it reads
        // worse than saying each one plainly.
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

    /**
     * The playing screen for the pairings the map is part of — as the question
     * (the country highlighted) or as the answer (a blank map to click). It
     * fills the viewport edge to edge: no card chrome boxing the map in.
     */
    private function buildPlayOnMap(int $total, int $answered): UIElement
    {
        $quiz = $this->quiz();

        $greens = $this->isosAnswered(Answer::Correct);
        $reds = $this->isosAnswered(Answer::Wrong);
        if ($this->wrong) {
            // A lenient miss shows red until the next try: where the map is the
            // answer that's the country clicked in error, and otherwise the one
            // being asked about, which is already the highlighted target.
            $reds[] = $quiz->mapIsAnswer() ? $this->pickedIso : $this->current()->code;
        }
        $reds = array_values(array_filter($reds, fn(string $iso) => $iso !== ''));

        $screen = UI::column()
            ->grow()
            ->minHeight(Unit::em(0))
            ->background(Palette::white())
            ->clipContent();

        // Escape passes on the question from anywhere on the screen. Where the
        // answer is typed the field owns that key; where it isn't, there is no
        // field, so the screen carries it.
        if (!$quiz->isTyped()) {
            $screen->onGlobalKeyDown(fn() => $this->next(), [Key::Escape], preventDefault: true);
        }

        $children = [
            $this->scoreBar($total, $answered),
            $this->buildMapPrompt(),
            UI::column()
                ->grow()
                ->minHeight(Unit::em(0))
                ->content(
                    new WorldMap(
                        // Nothing is highlighted when the map holds the answer;
                        // the highlight would be the answer.
                        $quiz->mapIsAnswer() ? '' : $this->current()->code,
                        $greens,
                        $reds,
                        fn(string $iso) => $this->handlePick($iso),
                        autoZoom: !$quiz->mapIsAnswer() && $this->autoZoom,
                    ),
                ),
            ...$this->buildAnswer(),
            new BrandInfo(fn() => $this->phase = GamePhase::Start, fn() => $this->finish()),
        ];

        return $screen->content(...$children);
    }

    /**
     * How the answer is given: typed into the field, picked out of a handful of
     * flags, or clicked straight on the map — in which case the answer area is
     * only the line telling you so.
     *
     * @return UIElement[]
     */
    private function buildAnswer(): array
    {
        $quiz = $this->quiz();

        if ($quiz->isTyped()) {
            return [
                new GuessInput(
                    $this->current()->code,
                    $this->destination->placeholder(),
                    $this->wrong,
                    fn(string $value) => $this->handleGuess($value),
                    fn() => $this->skip(),
                    fn() => $this->next(),
                ),
            ];
        }

        if ($quiz->picksFlag()) {
            return [
                new FlagChoices(
                    $this->flagChoices(),
                    $this->pickedIso,
                    fn(string $iso) => $this->judgePick($iso),
                ),
                $this->buildHint('Pick the flag · Esc to pass'),
            ];
        }

        return [$this->buildHint('Click the country · Esc to pass')];
    }

    /** The footer under a pointed answer: how to give one, and the way past it. */
    private function buildHint(string $text): UIElement
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
                UI::text($text),
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
     * The question on the card: whatever is being shown, given room — the flag
     * big enough to study, the words under it — and then the ask itself.
     */
    private function buildCardPrompt(): UIElement
    {
        $quiz = $this->quiz();
        $country = $this->current();

        $shown = [];
        if ($quiz->shows(Attribute::Flag)) {
            $shown[] = UI::image($country->bigUrl(), '')
                ->maxWidth(Unit::full())
                ->maxHeight(Unit::em(11))
                ->objectContain()
                ->rounded(Unit::px(6))
                ->shadow(Shadow::Small);
        }
        if ($quiz->shows(Attribute::Name)) {
            $shown[] = UI::text($country->name)
                ->center()
                ->weight(FontWeight::SemiBold)
                ->fontSize(FontSize::TwoXL)
                ->fontSize(FontSize::ThreeXL, Pseudo::sm());
        }
        if ($quiz->shows(Attribute::Capital)) {
            $shown[] = UI::text($country->capitalLabel())
                ->center()
                ->weight(FontWeight::SemiBold)
                ->fontSize(FontSize::TwoXL)
                ->fontSize(FontSize::ThreeXL, Pseudo::sm());
        }

        return UI::column()
            ->grow()
            ->minHeight(Unit::em(0))
            ->alignMiddle()
            ->alignCenter()
            ->gap(Unit::px(14))
            ->padding(Unit::px(24))
            ->padding(Unit::px(36), Pseudo::sm())
            ->content(...$shown, ...[$this->buildAsk()]);
    }

    /** The question above the map: the same things, in a line, over the answer. */
    private function buildMapPrompt(): UIElement
    {
        $quiz = $this->quiz();
        $country = $this->current();

        $shown = [];
        if ($quiz->shows(Attribute::Flag)) {
            $shown[] = UI::image($country->thumbUrl(), '')
                ->noShrink()
                ->width(Unit::px(60))
                ->height(Unit::px(40))
                ->objectContain()
                ->rounded(Unit::px(4))
                ->bordered()
                ->borderColor(Palette::border())
                ->shadow(Shadow::Small);
        }
        $words = [];
        if ($quiz->shows(Attribute::Name)) {
            $words[] = $country->name;
        }
        if ($quiz->shows(Attribute::Capital)) {
            $words[] = $country->capitalLabel();
        }
        if ($words !== []) {
            $shown[] = UI::text(implode(' · ', $words))
                ->weight(FontWeight::SemiBold)
                ->fontSize(FontSize::Large);
        }

        return UI::row()
            ->noShrink()
            ->wrap()
            ->alignMiddle()
            ->alignBetween()
            ->gap(Unit::px(12))
            ->bordered(bottom: 1)
            ->borderColor(Palette::border())
            ->padding(x: Unit::px(16), y: Unit::px(12))
            ->padding(x: Unit::px(24), pseudo: Pseudo::lg())
            ->content(
                UI::row()->wrap()->alignMiddle()->gap(Unit::px(12))->content(
                    ...$shown,
                    ...[$this->buildAsk()],
                ),
                // Auto-zoom only means something while the map is the question:
                // where it holds the answer there is no target to zoom to, and
                // zooming to one would be the answer.
                ...($quiz->mapIsAnswer()
                    ? []
                    : [new ChipToggle('Auto-zoom', $this->autoZoom, fn() => $this->toggleAutoZoom())]),
            );
    }

    /**
     * The ask, or the correction standing in for it — after a wrong answer that
     * can still be retried, what to say is what went wrong.
     */
    private function buildAsk(): UIElement
    {
        $picked = $this->pickedIso !== '' ? Country::byCode($this->pickedIso) : null;

        if ($this->wrong && $picked !== null) {
            return UI::text('That is ' . $picked->name . ' — try again')
                ->fontSize(FontSize::Small)
                ->weight(FontWeight::Medium)
                ->color(Palette::red());
        }

        return UI::text($this->quiz()->question())
            ->fontSize(FontSize::Small)
            ->color(Palette::subtle());
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
            $this->destination,
            $this->missedFlags(),
            fn() => $this->startGame(),
            fn() => $this->phase = GamePhase::Start,
            fn() => $this->retryMissed(),
        );
    }
}
