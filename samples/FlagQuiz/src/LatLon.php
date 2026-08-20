<?php

namespace Samples\FlagQuiz;

/**
 * A point on the globe, in degrees. A pair with names on it rather than an
 * array whose first element you have to remember the meaning of — and the two
 * are easy to swap by accident.
 */
final class LatLon
{
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
    ) {}
}
