<?php

namespace Samples\FlagQuiz;

/**
 * One landmark, placed: its name, where on earth it is, and a picture of it.
 * What the map needs to pin the Eiffel Tower rather than shade in France.
 *
 * The catalogue of them is generated ({@see LandmarkPlaces}); this is the
 * shape one entry takes. The picture may be missing — a few articles carry no
 * lead image — and a pin without one still names the place, which is the part
 * that matters.
 */
final class LandmarkPlace
{
    public function __construct(
        public readonly string $name,
        public readonly float $lat,
        public readonly float $lon,
        public readonly string $image = '',
    ) {}

    public function hasImage(): bool
    {
        return $this->image !== '';
    }
}
