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
use Samples\FlagQuiz\Components\FactStrip;
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
 * A game is a {@see Quiz}: some of a country's {@see Attribute}s shown — its
 * flag, its name, its capital, its place on the map, its landmarks, rivers,
 * mountains, big waters or its population — and one of them asked for. That
 * pairing decides everything downstream: how the question is drawn, whether
 * the map is the question or the answer, what an answer even is, and which
 * countries can be asked at all. So the screens below ask it rather than
 * testing for named modes.
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

    /**
     * The flags tab's question, kept while a place tab is open.
     *
     * There is only ever one question, so opening the Rivers tab has to
     * replace whatever the flags tab was spelling out. Without this, coming
     * back would land on the default and quietly undo a question that took
     * four clicks to build.
     *
     * @var Attribute[]
     */
    private array $flagSources = [Attribute::Flag];
    private Attribute $flagDestination = Attribute::Name;

    /** Explore mode: ISO-2 of the country currently focused on the map. */
    private string $exploreIso = '';

    /**
     * Explore mode: which of the facts the map draws — the landmarks pinned,
     * the rivers traced, the ranges and lakes shaded. Not what the strip says
     * about the country, which is always all of it; this is the map underneath.
     *
     * A standing preference, not anything about the country focused, so it
     * holds while you walk down the list — the whole use of it being to look
     * at one thing across many countries. May be emptied: a plain map with
     * nothing drawn on it is a fair way to want it.
     *
     * @var Attribute[]
     */
    private array $exploreLayers = [];

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
        // Same again for Explore's map layers — everything drawn to begin
        // with. A session that turned them all off still restores empty: the
        // saved state is one array for the whole component, so an empty entry
        // in it is a stored choice and not a missing one.
        if ($this->exploreLayers === []) {
            $this->exploreLayers = Attribute::layers();
        }

        $this->useState($this->phase);
        $this->useState($this->mode);
        $this->useState($this->sources);
        $this->useState($this->destination);
        $this->useState($this->flagSources);
        $this->useState($this->flagDestination);
        $this->useState($this->exploreIso);
        $this->useState($this->exploreLayers);
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
        // Nothing to ask: the continents chosen hold no country carrying every
        // fact the question names. Stay where we are rather than start a game
        // with no questions in it — the start screen says as much, and the
        // player fixes it by widening the selection or asking something else.
        if ($indexes === []) {
            return;
        }

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
    /**
     * A click on the open map, when what is being asked for is a place rather
     * than a country. Judged against where the place actually is — near the
     * Zambezi is right whether the click fell in Zambia, Zimbabwe or the water
     * between them, which is the point of asking about the river instead of
     * about its countries.
     *
     * A miss records nothing to read back: there is no name for "somewhere in
     * the Atlantic", and the results screen already says what the answer was.
     */
    private function handleLocate(float $lat, float $lon): void
    {
        $place = $this->quiz()->place();
        if ($place === null) {
            return;
        }
        $this->judge(PlaceLocator::hits($place, $this->current(), $lat, $lon));
    }

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

    /**
     * Where Explore's map opens: on the first continent chosen. The world at
     * zoom 2 is too far out for anything drawn on it to be read — a river is a
     * hair and a landmark pin covers three countries — and the continents
     * picked are the best statement of where you meant to be looking.
     *
     * The first rather than all of them, because "all of them" is the world
     * again. It is only the opening view; the map is yours to move afterwards.
     */
    private function exploreOpensAt(): MapView
    {
        foreach (Continent::cases() as $continent) {
            if (in_array($continent, $this->continents, true)) {
                return $continent->view();
            }
        }
        return MapView::world();
    }

    /**
     * Draw this fact on Explore's map, or stop drawing it. Unlike the
     * continents, the last one may be turned off: nothing drawn leaves a plain
     * map, which is what Explore had before any of this and a fair way to want
     * it. What the strip *says* about the country is untouched either way.
     */
    private function toggleExploreLayer(Attribute $fact): void
    {
        if (in_array($fact, $this->exploreLayers, true)) {
            $this->exploreLayers = array_values(
                array_filter($this->exploreLayers, fn(Attribute $a) => $a !== $fact),
            );
            return;
        }
        $this->exploreLayers[] = $fact;
        // Kept in the lists' own order, however they were ticked, so the map
        // stacks its layers the same way round every time.
        $order = array_flip(array_map(fn(Attribute $a) => $a->value, Attribute::cases()));
        usort($this->exploreLayers, fn(Attribute $a, Attribute $b) => $order[$a->value] <=> $order[$b->value]);
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
            // A river shown beside a flag would be a question claiming the one
            // belongs to the other, which is the pairing that isn't offered
            // ({@see Attribute::compatible()}). The row is greyed, so this is
            // the belt to that braces.
            if (!$this->quiz()->accepts($attribute)) {
                return;
            }
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

        // Whatever was shown stays shown, less the attribute now being asked
        // for — and less anything the new question rules out: ask for a river
        // and the flag on the left has to go, since a river is not the flag's
        // to have.
        $sources = array_values(array_filter(
            $this->sources,
            fn(Attribute $a) => $a !== $attribute && Attribute::compatible($a, $attribute),
        ));
        // Nothing survived. The attribute just replaced takes its place where
        // it can — asking for what you were shown simply turns the question
        // around — and otherwise the first thing that pairs with it does.
        if ($sources === []) {
            $sources = [Attribute::compatible($replaced, $attribute)
                ? $replaced
                : self::firstCompatible($attribute)];
        }
        $this->sources = $sources;
        $this->sortSources();
        $this->settleFlagSort();
    }

    /**
     * Something that can be shown when this is what's asked for. There is
     * always one: the facts pair with each other, and the four a country is
     * known by pair among themselves.
     */
    private static function firstCompatible(Attribute $destination): Attribute
    {
        foreach (Attribute::quizzable() as $attribute) {
            if ($attribute !== $destination && Attribute::compatible($attribute, $destination)) {
                return $attribute;
            }
        }
        return Attribute::Flag;
    }

    /**
     * Open a tab — the flags builder, or one of the places.
     *
     * A tab is not a thing the game remembers; it is read back off the
     * question ({@see Quiz::place()}). So opening one means setting the
     * question to that tab's, and the flags tab's is put aside first so that
     * coming back to it finds what was left there.
     */
    private function selectTab(string $key): void
    {
        $place = Attribute::tryFrom($key);
        if ($place === null || !$place->isPlace()) {
            $this->sources = $this->flagSources;
            $this->destination = $this->flagDestination;
            $this->settleFlagSort();
            return;
        }

        $this->rememberFlagsQuestion();
        // Which way round the last place question ran carries over: someone
        // finding rivers on the map is likely to want to find mountains the
        // same way, and being put back to naming every time would be a click
        // to undo on every tab.
        $this->setPlaceQuestion($place, PlaceQuestion::of($this->quiz()) ?? PlaceQuestion::Name);
    }

    /** Ask this place's question the other way round. */
    private function selectPlaceQuestion(Attribute $place, PlaceQuestion $question): void
    {
        $this->rememberFlagsQuestion();
        $this->setPlaceQuestion($place, $question);
    }

    private function setPlaceQuestion(Attribute $place, PlaceQuestion $question): void
    {
        $quiz = $question->quizFor($place);
        $this->sources = $quiz->sources;
        $this->destination = $quiz->destination;
        $this->settleFlagSort();
    }

    /**
     * Put the flags tab's question aside, if that is what is on screen. Only
     * then — moving from one place tab to another must not overwrite it with a
     * question about rivers.
     */
    private function rememberFlagsQuestion(): void
    {
        if ($this->quiz()->place() === null) {
            $this->flagSources = $this->sources;
            $this->flagDestination = $this->destination;
        }
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

    /**
     * The countries a game would actually be played over: those on a chosen
     * continent that can answer the question being asked. The second half of
     * that matters now that a question can be about a river or a mountain
     * range — Malta has neither, and a round including it would be a round
     * with an unanswerable question in it.
     *
     * @return int[] indices into Country::all()
     */
    private function selectedCountryIndexes(): array
    {
        $quiz = $this->quiz();
        $out = [];
        foreach (Country::all() as $i => $country) {
            if (in_array($country->continent, $this->continents, true) && $quiz->covers($country)) {
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
                    // One playing screen for every pairing there is: the map
                    // ones fill the viewport, the rest sit on a card.
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
                        fn(string $key) => $this->selectTab($key),
                        fn(Attribute $place, PlaceQuestion $question)
                            => $this->selectPlaceQuestion($place, $question),
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
                        // the highlight would be the answer. Nor when the
                        // question is about a place: the country it falls in is
                        // beside the point, and shading it in would answer a
                        // question nobody asked.
                        $quiz->mapIsAnswer() || $quiz->place() !== null ? '' : $this->current()->code,
                        $greens,
                        $reds,
                        fn(string $iso) => $this->handlePick($iso),
                        autoZoom: !$quiz->mapIsAnswer() && $this->autoZoom,
                        // A question about a river shows the river, not the
                        // country it runs through — unless the river is what is
                        // being looked for, in which case drawing it would be
                        // drawing the answer (see Quiz::mappableFacts()).
                        factsOf: [$this->current()],
                        factLayers: $quiz->mappableFacts(),
                        // Shown to be named, a place is drawn with nothing
                        // naming it. The picture of a landmark is the question.
                        namePlaces: !$quiz->namesPlace(),
                        // Shown to be named, the place has to be visible:
                        // at world zoom a landmark photo is four pixels
                        // across. Zoomed to once per question.
                        fitToPlaces: $quiz->namesPlace(),
                        // Finding a place is answered by pointing at the place,
                        // so the answer is where the click landed rather than
                        // which country it landed on.
                        onLocate: $quiz->locatesPlace()
                            ? fn(float $lat, float $lon) => $this->handleLocate($lat, $lon)
                            : null,
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
        foreach ($quiz->contentSources() as $source) {
            if ($source !== Attribute::Flag) {
                $shown[] = $this->buildFact($source, $country);
            }
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

    /**
     * One shown fact on the card: the value, with the name of the attribute
     * over it where the value doesn't say what it is. "Cairo" is plainly a
     * capital; "Nile" could be a river, a landmark or a dam, and three facts
     * stacked namelessly would be a puzzle about the question rather than the
     * question.
     *
     * The list-valued facts name several things at once and are set smaller
     * for it — four landmarks at heading size are a paragraph, not a prompt.
     */
    private function buildFact(Attribute $attribute, Country $country): UIElement
    {
        $value = UI::text($attribute->textOf($country))
            ->center()
            ->weight(FontWeight::SemiBold);
        if ($attribute->isList()) {
            $value->fontSize(FontSize::Large)->fontSize(FontSize::TwoXL, Pseudo::sm());
        } else {
            $value->fontSize(FontSize::TwoXL)->fontSize(FontSize::ThreeXL, Pseudo::sm());
        }

        if (!$attribute->needsLabel()) {
            return $value;
        }

        return UI::column()
            ->alignCenter()
            ->gap(Unit::px(4))
            ->content(
                UI::text($attribute->label())
                    ->center()
                    ->fontSize(FontSize::ExtraSmall)
                    ->uppercase()
                    ->color(Palette::labelMuted()),
                $value,
            );
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
        // In a line there is no room to stack a caption over a value, so the
        // caption goes in front of it — small, grey and set in capitals, so it
        // reads as the name of the thing rather than as part of it. Written
        // that way and not as "Lake: Lake Toba", which says lake twice and
        // still leaves the two words looking like one phrase.
        foreach ($quiz->contentSources() as $source) {
            if ($source === Attribute::Flag) {
                continue;
            }
            $value = UI::text($source->textOf($country))
                ->weight(FontWeight::SemiBold)
                ->fontSize(FontSize::Large);
            $shown[] = $source->needsLabel()
                ? UI::row()->alignMiddle()->gap(Unit::px(7))->content(
                    UI::text($source->label())
                        ->noShrink()
                        ->fontSize(FontSize::ExtraSmall)->uppercase()->color(Palette::labelMuted()),
                    $value,
                )
                : $value;
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
        // The countries Explore is about: the chosen continents. The list on
        // the left and the facts drawn on the map are the same set, so turning
        // a continent off takes it out of both.
        $exploring = array_values(array_filter(
            Country::all(),
            fn(Country $c) => in_array($c->continent, $this->continents, true),
        ));

        // Fullscreen: the map fills the viewport edge-to-edge — no card chrome
        // (margins / border / rounding / shadow) boxing it in.
        $screen = UI::column()
            ->grow()
            ->minHeight(Unit::em(0))
            ->background(Palette::white())
            ->clipContent();

        // The head of the screen: the title row, and under it the focused
        // country's facts. Both live inside one element rather than sitting as
        // two children of the screen, and deliberately: the map below is a
        // Leaflet instance built once and kept, and appearing or vanishing
        // between it and the top of the screen moves it a place along and has
        // it rebuilt from scratch — the map blanks the moment you pick a
        // country. Nested here, the screen always has exactly two children and
        // the map keeps its place whatever the header does.
        $head = UI::column()->noShrink();

        $children = [
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
                                // Name over capital: the country is the heading
                                // and its capital the smaller line under it, so
                                // the pair reads as one caption on the flag.
                                UI::column()->gap(Unit::px(1))->content(
                                    UI::text($selected->name)
                                        ->weight(FontWeight::SemiBold)->fontSize(FontSize::Large),
                                    UI::text($selected->capitalLabel())
                                        ->fontSize(FontSize::Small)->color(Palette::subtle()),
                                ),
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
        ];

        // What the quiz can ask about this country, laid out for reading, over
        // the chips saying which of it the map should draw. Explore is where
        // the flags and the places are learned; the facts have to be somewhere
        // too, or a landmarks round is a test with no book to study from.
        // Always on screen, with or without a country focused — the chips are
        // how you find out Explore has any of this.
        $children[] = new FactStrip(
            $selected,
            $this->exploreLayers,
            fn(Attribute $fact) => $this->toggleExploreLayer($fact),
        );

        return $screen->content(
            $head->content(...$children),
                // Body: list (left) + map (right); stacks on small screens.
                UI::column()
                    ->grow()
                    ->minHeight(Unit::em(0))
                    ->direction(Direction::row(), Pseudo::lg())
                    ->content(
                        new CountryList(
                            $exploring,
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
                                    // Facts drawn where they actually are — the
                                    // landmarks pinned, the rivers traced — as
                                    // the chips ask for. One country's while one
                                    // is focused; everything in play when none
                                    // is, so the chips mean something before
                                    // anything has been clicked.
                                    factsOf: $selected !== null ? [$selected] : $exploring,
                                    factLayers: $this->exploreLayers,
                                    // Opens on the first continent chosen
                                    // rather than on the whole world, which is
                                    // too far out to read anything drawn on it.
                                    opensAt: $this->exploreOpensAt(),
                                    showCountries: in_array(Attribute::Location, $this->exploreLayers, true),
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
