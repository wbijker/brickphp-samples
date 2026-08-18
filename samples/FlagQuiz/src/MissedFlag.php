<?php

namespace Samples\FlagQuiz;

/**
 * One flag the player did not get: the country, its position in the shuffled
 * order (so a retry can rebuild the round), and the last thing they typed for
 * it — '' when they never typed anything, which is the case for a straight
 * skip and for a wrong pick on the map.
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
    ) {}
}
