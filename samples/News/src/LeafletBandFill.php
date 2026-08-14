<?php

namespace Samples\News;

use JsonSerializable;

/**
 * A banded fill for GeoJSON features: a list of colours painted as equal,
 * hard-edged bands across whatever shape uses it. Declare the fills a map may
 * use with {@see Leaflet::bandFills()}, then point a feature style's
 * `fillColor` at one with {@see fill()}:
 *
 *   $dutch = new LeafletBandFill('nl', ['#ae1c28', '#ffffff', '#21468b']);
 *   $map->bandFills([$dutch]);
 *   $map->styleFeatures(['nl' => ['fillColor' => $dutch->fill()]]);
 *
 * Each fill becomes an SVG `<linearGradient>` carrying two stops per colour, so
 * the bands stay hard-edged rather than blending. The gradient is measured
 * against each shape's own bounding box, so one definition can paint any number
 * of features and every one gets full-height bands.
 */
final class LeafletBandFill implements JsonSerializable
{
    /**
     * Prefix for the generated SVG gradient id. The client runtime builds the
     * same string, so the two must stay in step.
     */
    private const ID_PREFIX = 'brick-band-';

    /**
     * @param string   $id       unique within the map; forms the gradient's id
     * @param string[] $colors   CSS colours, in the order the bands are painted
     * @param bool     $vertical bands run left→right instead of top→bottom
     */
    public function __construct(
        public readonly string $id,
        public readonly array $colors,
        public readonly bool $vertical = false,
    ) {}

    /** The `fillColor` value that paints a feature with this fill. */
    public function fill(): string
    {
        return 'url(#' . self::ID_PREFIX . $this->id . ')';
    }

    /** @return array{id: string, colors: string[], vertical: bool} */
    public function jsonSerialize(): array
    {
        return ['id' => $this->id, 'colors' => $this->colors, 'vertical' => $this->vertical];
    }
}
