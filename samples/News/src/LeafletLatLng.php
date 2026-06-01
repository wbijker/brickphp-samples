<?php

namespace Samples\News;

/**
 * A geographic coordinate. Mirrors Leaflet's `L.LatLng` shape — the
 * pair of fields that arrive in every Leaflet event with location
 * data.
 */
class LeafletLatLng
{
    public function __construct(
        public readonly float $lat,
        public readonly float $lng,
    ) {}

    /** @param array{lat: float, lng: float} $raw */
    public static function from(array $raw): self
    {
        return new self((float) $raw['lat'], (float) $raw['lng']);
    }
}
