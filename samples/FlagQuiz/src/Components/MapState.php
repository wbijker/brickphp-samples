<?php

namespace Samples\FlagQuiz\Components;

use JsonSerializable;

/**
 * The map's render state, pushed to the Leaflet glue as JSON: which country to
 * highlight (blue), the ones already correct (green) / wrong (red), whether to
 * show flag-and-name labels, and whether to auto-zoom to the target. The
 * `jsonSerialize()` shape is the client contract; the rest of the app passes
 * this typed object rather than a loose associative array.
 */
final class MapState implements JsonSerializable
{
    /**
     * @param string[] $greens ISO-2 codes answered correctly
     * @param string[] $reds   ISO-2 codes answered wrong
     */
    public function __construct(
        public readonly string $target,
        public readonly array $greens,
        public readonly array $reds,
        public readonly bool $labels,
        public readonly bool $autoZoom,
    ) {}

    /** @return array<string, mixed> the JSON contract consumed by the map glue */
    public function jsonSerialize(): array
    {
        return [
            'target' => $this->target,
            'greens' => array_values($this->greens),
            'reds' => array_values($this->reds),
            'labels' => $this->labels,
            'autoZoom' => $this->autoZoom,
        ];
    }
}
