<?php

namespace Samples\FlagQuiz;

/**
 * How the questions are ordered for a game, chosen from the start-screen
 * dropdown. The Flags-mode options group visually similar flags next to each
 * other so lookalikes (Chad/Romania, Indonesia/Monaco, …) can be compared;
 * Locations mode instead offers "By Continent" so the map walks region by
 * region. Which options apply to which mode is decided by {@see forMode()}.
 *
 * String-backed so it round-trips through the session state and the <select>'s
 * change event. The flag groupings are driven by {@see FlagTraits}.
 */
enum FlagSort: string
{
    case Random = 'random';
    case Color = 'color';
    case Shape = 'shape';
    case ShapeColor = 'shape-color';
    case Similarity = 'similarity';
    case Continent = 'continent';

    /** Label for the dropdown option. */
    public function label(): string
    {
        return match ($this) {
            self::Random => 'Random',
            self::Color => 'By Color',
            self::Shape => 'By Shape',
            self::ShapeColor => 'By Shape + Color',
            self::Similarity => 'By Similarity',
            self::Continent => 'By Continent',
        };
    }

    /**
     * The order options offered for a game mode. Locations is map-based, so the
     * flag-appearance groupings make no sense there — only Random and the
     * region grouping apply.
     *
     * @return self[]
     */
    public static function forMode(GameMode $mode): array
    {
        return $mode === GameMode::Location
            ? [self::Random, self::Continent]
            : [self::Random, self::Color, self::Shape, self::ShapeColor, self::Similarity];
    }

    /**
     * The grouping key for a country under this ordering. Countries sharing a key
     * are placed adjacently; Random yields '' so every one compares equal
     * (leaving the prior shuffle untouched).
     */
    public function keyFor(Country $country): string
    {
        $traits = FlagTraits::for($country->code);
        return match ($this) {
            self::Random => '',
            self::Color => $traits->colorKey(),
            self::Shape => $traits->shape->value,
            self::ShapeColor => $traits->shape->value . '|' . $traits->colorKey(),
            self::Similarity => $traits->similarityKey(),
            self::Continent => $country->continent->value,
        };
    }
}
