<?php

namespace Samples\News;

/**
 * An area drawn on the map — a mountain range, a lake, anything with an inside.
 * Like {@see LeafletLine} it takes several rings, since one named thing is
 * often several shapes: a lake with islands in it, a range that the data
 * breaks into massifs.
 *
 * @see LeafletOverlay for why these are pushed per render.
 */
final class LeafletArea extends LeafletOverlay
{
    /**
     * @param array<int, array<int, array{0: float, 1: float}>> $rings one or
     *   more closed runs of [latitude, longitude] points
     * @param array<string, mixed> $style Leaflet path options (color, fillColor, …)
     */
    public function __construct(
        private readonly array $rings,
        private readonly array $style = [],
    ) {}

    public function toArray(): array
    {
        return ['kind' => 'area', 'rings' => $this->rings, 'style' => $this->style];
    }
}
