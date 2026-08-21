<?php

namespace Samples\FlagQuiz;

/**
 * Where a map opens: a point to centre on and how close to sit. What a
 * latitude and a longitude alone cannot say — the same centre at zoom 2 is the
 * world and at zoom 5 is a country.
 */
final class MapView
{
    public function __construct(
        public readonly LatLon $centre,
        public readonly int $zoom,
    ) {}

    /** The whole world, which is where a map with nothing better to show opens. */
    public static function world(): self
    {
        return new self(new LatLon(25, 0), 2);
    }

    /** As Leaflet takes a centre: [latitude, longitude]. */
    public function coordinates(): array
    {
        return [$this->centre->latitude, $this->centre->longitude];
    }
}
