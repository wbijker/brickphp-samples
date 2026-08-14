<?php

namespace Samples\News;

use JsonSerializable;

/**
 * An image fill for GeoJSON features: a picture painted inside whatever shape
 * uses it. Declare the fills a map may use with {@see Leaflet::imageFills()},
 * then point a feature style's `fillColor` at one with {@see fill()}:
 *
 *   $dutch = new LeafletImageFill('nl', 'https://flagcdn.com/w320/nl.png');
 *   $map->imageFills([$dutch]);
 *   $map->styleFeatures(['nl' => ['fillColor' => $dutch->fill(), 'fillOpacity' => 1]]);
 *
 * Each fill becomes an SVG `<pattern>` holding one `<image>` stretched over the
 * shape's bounding box, so the whole picture always shows and is then clipped to
 * the outline. Stretched, not cropped: a country is rarely the shape of a flag,
 * and showing all of it matters more here than keeping its proportions.
 */
final class LeafletImageFill implements JsonSerializable
{
    /**
     * Prefix for the generated SVG pattern id. The client runtime builds the
     * same string, so the two must stay in step.
     */
    private const ID_PREFIX = 'brick-fill-';

    /**
     * @param string $id  unique within the map; forms the pattern's id
     * @param string $url image to paint — anything an `<img>` could load
     */
    public function __construct(
        public readonly string $id,
        public readonly string $url,
    ) {}

    /** The `fillColor` value that paints a feature with this fill. */
    public function fill(): string
    {
        return 'url(#' . self::ID_PREFIX . $this->id . ')';
    }

    /** @return array{id: string, url: string} */
    public function jsonSerialize(): array
    {
        return ['id' => $this->id, 'url' => $this->url];
    }
}
