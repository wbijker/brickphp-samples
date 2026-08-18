<?php

namespace Samples\FlagQuiz;

/**
 * What a run of letters in a guess/answer comparison turned out to be.
 * Deliberately free of colours — the screen rendering the diff decides how
 * each kind looks, as {@see Components\StatTone} does for the score cells.
 */
enum DiffKind
{
    /** Typed, and right where the answer has it. */
    case Same;

    /** In the answer, but missing from what was typed. */
    case Missing;

    /** Typed, but not in the answer. */
    case Added;
}
