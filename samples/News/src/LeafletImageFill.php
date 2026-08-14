<?php

namespace Samples\News;

use JsonSerializable;

/**
 * A tiled image fill for GeoJSON features: a picture repeated across whatever
 * shape uses it. Declare the fills a map may use with
 * {@see Leaflet::imageFills()}, then point a feature style's `fillColor` at one
 * with {@see fill()}:
 *
 *   $dutch = new LeafletImageFill('nl', 'https://flagcdn.com/w320/nl.png');
 *   $map->imageFills([$dutch]);
 *   $map->styleFeatures(['nl' => ['fillColor' => $dutch->fill(), 'fillOpacity' => 1]]);
 *
 * Each fill becomes an SVG `<pattern>` whose tile is {@see $tile} pixels tall,
 * with the width taken from the picture's own proportions — so the image is
 * never stretched to fit the shape it lands in. The tile is measured in screen
 * pixels, so it stays the same readable size at every zoom and a bigger shape
 * simply holds more copies. Tiles share one origin across the map, so the
 * repeats line up across neighbouring shapes.
 *
 * {@see $gap} sets copies apart so each reads as one picture rather than
 * running into its neighbours, and {@see $background} paints what shows between
 * them — leave it null and that is whatever lies under the shape.
 */
final class LeafletImageFill implements JsonSerializable
{
    /**
     * Prefix for the generated SVG pattern id. The client runtime builds the
     * same string, so the two must stay in step.
     */
    private const ID_PREFIX = 'brick-fill-';

    /**
     * @param string      $id         unique within the map; forms the pattern's id
     * @param string      $url        image to paint — anything an `<img>` could load
     * @param int         $tile       tile height in screen pixels; the width follows
     *                                the image's own aspect ratio
     * @param int         $gap        screen pixels left between neighbouring copies
     * @param string|null $background CSS colour shown in the gap; null leaves it clear
     */
    public function __construct(
        public readonly string $id,
        public readonly string $url,
        public readonly int $tile = 24,
        public readonly int $gap = 0,
        public readonly ?string $background = null,
    ) {}

    /** The `fillColor` value that paints a feature with this fill. */
    public function fill(): string
    {
        return 'url(#' . self::ID_PREFIX . $this->id . ')';
    }

    /** @return array{id: string, url: string, tile: int, gap: int, background: string|null} */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'tile' => $this->tile,
            'gap' => $this->gap,
            'background' => $this->background,
        ];
    }
}
