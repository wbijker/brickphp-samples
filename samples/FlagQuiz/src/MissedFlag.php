<?php

namespace Samples\FlagQuiz;

/**
 * One flag the player did not get: the country, its position in the shuffled
 * order (so a retry can rebuild the round), and the last answer they gave for
 * it — '' when they gave none, which is the case for a straight skip.
 *
 * The guess carries how it was made as well as what it was: typed into the
 * field, or the name of a country picked off the map or out of the flags on
 * offer. The two read
 * back differently (see {@see GuessKind}).
 *
 * A sibling of {@see RemainingFlag}: the review list needs country *and* guess
 * together, which is a type, not a pair of parallel arrays.
 */
final class MissedFlag
{
    public function __construct(
        public readonly int $pos,
        public readonly Country $country,
        public readonly string $guess = '',
        public readonly GuessKind $kind = GuessKind::Typed,
    ) {}
}
