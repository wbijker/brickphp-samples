<?php

namespace Samples\News;

/**
 * A line drawn on the map — a river's course, a border, a route. Carries one
 * or more paths, because the thing being drawn is often not one unbroken line:
 * Natural Earth holds the Nile as a string of separate segments, and drawing
 * them as one polyline would join its mouth to its source.
 *
 * @see LeafletOverlay for why these are pushed per render.
 */
final class LeafletLine extends LeafletOverlay
{
    /**
     * @param array<int, array<int, array{0: float, 1: float}>> $paths one or
     *   more runs of [latitude, longitude] points
     * @param array<string, mixed> $style Leaflet path options (color, weight, …)
     */
    public function __construct(
        private readonly array $paths,
        private readonly array $style = [],
    ) {}

    public function toArray(): array
    {
        return ['kind' => 'line', 'paths' => $this->paths, 'style' => $this->style];
    }
}
