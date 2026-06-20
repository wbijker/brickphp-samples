<?php

namespace Samples\FlagQuiz\Components;

/**
 * The visual treatment of a {@see ScoreBar} stat cell. `Ink` is a plain neutral
 * value; `Right` / `Wrong` are the running tallies that colour green / red and
 * re-trigger the "pop" animation when they change.
 */
enum StatTone
{
    case Ink;
    case Right;
    case Wrong;

    /** Whether this cell animates (and re-keys) on every value change. */
    public function isTally(): bool
    {
        return $this !== self::Ink;
    }
}
