<?php

namespace Samples\FlagQuiz;

/**
 * The outcome of a single question. `Pending` is an unanswered slot; the rest
 * are terminal. Used both per order-position (the live board) and, for its
 * `Correct` / `Wrong` cases, as the running attempt history. Persisted as
 * session state (see {@see GamePhase}).
 */
enum Answer: string
{
    case Pending = '';
    case Correct = 'correct';
    case Skipped = 'skipped';
    case Wrong = 'wrong';

    /** True once the question has been decided (not still pending). */
    public function isDecided(): bool
    {
        return $this !== self::Pending;
    }
}
