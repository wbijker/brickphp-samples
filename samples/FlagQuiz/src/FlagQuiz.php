<?php

namespace Samples\FlagQuiz;

use BrickPHP\Js\Js;
use BrickPHP\UI\Direction;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use Samples\FlagQuiz\Components\BrandInfo;
use Samples\FlagQuiz\Components\FlagGrid;
use Samples\FlagQuiz\Components\GuessPanel;
use Samples\FlagQuiz\Components\ScoreBar;
use Samples\FlagQuiz\Screens\FinishedScreen;
use Samples\FlagQuiz\Screens\StartScreen;

/**
 * Flagdle — the game's stateful core. Holds the session state and the rules,
 * and composes the screens/components ({@see StartScreen}, {@see ScoreBar},
 * {@see GuessPanel}, {@see FlagGrid}, {@see FinishedScreen}) that render it.
 * Country data lives in {@see Country}, colours in {@see Palette}.
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
    /** Number of segments in the progress bar. */
    private const PROGRESS_SEGMENTS = 40;

    private string $phase = 'start';

    /** Settings, chosen on the start screen and kept across games. */
    private bool $showFlags = true;
    private bool $strict = true;

    /** @var int[] shuffled indices into Country::all() */
    private array $order = [];
    private int $index = 0;
    /** @var string[] one entry per order position: '' | 'correct' | 'skipped' | 'wrong' */
    private array $status = [];
    private bool $wrong = false;
    /**
     * One entry per *decided* flag, in order: 'correct' | 'wrong'. Only a
     * correct answer or a strict-mode wrong answer (where we move on) records
     * an entry — a retryable wrong guess does not.
     *
     * @var string[]
     */
    private array $history = [];
    private int $startTime = 0;
    private int $elapsed = 0;

    protected function initialize(): void
    {
        $this->useState($this->phase);
        $this->useState($this->showFlags);
        $this->useState($this->strict);
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

    private function startGame(): void
    {
        $n = count(Country::all());
        $this->order = range(0, $n - 1);
        shuffle($this->order);
        $this->index = 0;
        $this->status = array_fill(0, $n, '');
        $this->wrong = false;
        $this->history = [];
        $this->elapsed = 0;
        $this->startTime = time();
        $this->phase = 'playing';
    }

    /**
     * Handle a submitted guess. A correct guess always advances. A wrong guess
     * is final (marked and advanced) in strict mode, otherwise it just flags
     * the input red so the player can retry.
     */
    private function handleGuess(string $value): void
    {
        if ($this->current()->matches($value)) {
            $this->status[$this->index] = 'correct';
            $this->wrong = false;
            $this->history[] = 'correct';
            $this->advance();
        } elseif ($this->strict) {
            // Strict: a wrong guess is final and we move on, so it's recorded.
            $this->status[$this->index] = 'wrong';
            $this->wrong = false;
            $this->history[] = 'wrong';
            $this->advance();
        } else {
            // Lenient: stay on the flag for a retry — nothing recorded yet.
            $this->wrong = true;
        }
    }

    private function skip(): void
    {
        $this->status[$this->index] = 'skipped';
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
        $this->phase = 'finished';
    }

    private function toggleShowFlags(): void
    {
        $this->showFlags = !$this->showFlags;
    }

    private function toggleStrict(): void
    {
        $this->strict = !$this->strict;
    }

    private function advance(): void
    {
        $n = count($this->order);
        for ($k = 1; $k <= $n; $k++) {
            $j = ($this->index + $k) % $n;
            if (($this->status[$j] ?? '') === '') {
                $this->index = $j;
                return;
            }
        }
        $this->elapsed = time() - $this->startTime;
        $this->wrong = false;
        $this->phase = 'finished';
    }

    private function jumpTo(int $pos): void
    {
        if (($this->status[$pos] ?? '') !== '') {
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

    // ============================================================
    // Render
    // ============================================================

    protected function build(): VNode
    {
        $total = count(Country::all());
        $answered = count(array_filter($this->status, fn(string $s) => $s !== ''));

        return UI::column()
            ->minHeight(Unit::vh(100))
            ->height(Unit::vh(100), Pseudo::lg())
            ->clipContent(Pseudo::lg())
            ->width(Unit::full())
            ->background(Palette::page())
            ->color(Palette::ink())
            ->content(
                $this->buildProgress($total, $answered),
                match ($this->phase) {
                    'playing' => $this->buildPlay($total, $answered),
                    'finished' => $this->buildFinished($total),
                    default => new StartScreen(
                        $total,
                        $this->showFlags,
                        $this->strict,
                        fn() => $this->startGame(),
                        fn() => $this->toggleShowFlags(),
                        fn() => $this->toggleStrict(),
                    ),
                }
            );
    }

    private function buildProgress(int $total, int $answered): UIElement
    {
        $done = $this->phase === 'finished' ? $total : $answered;
        $filled = $total > 0 ? (int)round($done / $total * self::PROGRESS_SEGMENTS) : 0;

        $segments = [];
        for ($i = 0; $i < self::PROGRESS_SEGMENTS; $i++) {
            $segments[] = UI::container()
                ->grow()
                ->extendY()
                ->background($i < $filled ? Palette::blue() : Palette::track());
        }

        return UI::row()
            ->height(Unit::px(3))
            ->width(Unit::full())
            ->noShrink()
            ->content(...$segments);
    }

    private function buildPlay(int $total, int $answered): UIElement
    {
        $correct = count(array_filter($this->status, fn(string $s) => $s === 'correct'));
        $score = $answered > 0 ? (int)round($correct / $answered * 100) : 0;
        $time = $this->fmtTime(time() - $this->startTime);

        $right = count(array_filter($this->history, fn(string $r) => $r === 'correct'));
        $wrong = count($this->history) - $right;

        $remaining = $this->remainingItems();
        $showGrid = $this->showFlags && count($remaining) > 0;

        // One off-white block: stacks vertically on small screens, splits
        // left/right at lg. The left panel is white; the flag grid shows the
        // block's off-white through.
        return UI::column()
            ->grow()
            ->minHeight(Unit::px(0))
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
            new BrandInfo(fn() => $this->phase = 'start', fn() => $this->finish()),
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

    /** @return array<array{pos:int, country:Country}> all unanswered (incl. current) */
    private function remainingItems(): array
    {
        $all = Country::all();
        $out = [];
        foreach ($this->order as $pos => $countryIdx) {
            if (($this->status[$pos] ?? '') === '') {
                $out[] = ['pos' => $pos, 'country' => $all[$countryIdx]];
            }
        }
        return $out;
    }

    private function buildFinished(int $total): VNode
    {
        $all = Country::all();
        $correct = count(array_filter($this->status, fn(string $s) => $s === 'correct'));
        $answered = count(array_filter($this->status, fn(string $s) => $s !== ''));
        // Accuracy over what was actually attempted (so an early "Done" isn't
        // diluted by flags never reached).
        $accuracy = $answered > 0 ? (int)round($correct / $answered * 100) : 0;

        // The flags actually got wrong or gave up on — not the ones never
        // reached (status '' when finishing early).
        $missed = [];
        foreach ($this->order as $pos => $countryIdx) {
            $status = $this->status[$pos] ?? '';
            if ($status === 'wrong' || $status === 'skipped') {
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
            fn() => $this->phase = 'start',
        );
    }
}
