<?php

namespace Samples\FlagQuiz;

/**
 * One still-unanswered question: a country paired with its position in the
 * shuffled order, so the flag grid can render it and jump the quiz back to it.
 * Replaces what used to be a `['pos' => …, 'country' => …]` associative array.
 */
final class RemainingFlag
{
    public function __construct(
        public readonly int $pos,
        public readonly Country $country,
    ) {}
}
