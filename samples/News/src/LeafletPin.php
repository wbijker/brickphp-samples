<?php

namespace Samples\News;

/**
 * A pin at one point, drawn as whatever HTML it is given rather than as
 * Leaflet's default teardrop — so a landmark can be pinned as its own picture
 * with its name beside it.
 *
 * The HTML is styled from a stylesheet by its class rather than inline, the
 * same way the GeoJSON layer's tooltips are: the pin is markup handed to the
 * map, and the app that supplies it owns how it looks.
 *
 * @see LeafletOverlay for why these are pushed per render.
 */
final class LeafletPin extends LeafletOverlay
{
    public function __construct(
        private readonly float $lat,
        private readonly float $lon,
        private readonly string $html,
        private readonly string $className = '',
    ) {}

    public function toArray(): array
    {
        return [
            'kind' => 'pin',
            'at' => [$this->lat, $this->lon],
            'html' => $this->html,
            'className' => $this->className,
        ];
    }
}
