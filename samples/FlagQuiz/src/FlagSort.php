<?php

namespace Samples\FlagQuiz;

/**
 * How the questions are ordered for a game, chosen from the start-screen
 * dropdown. The appearance groupings put visually similar flags next to each
 * other so lookalikes (Chad/Romania, Indonesia/Monaco, …) can be compared, and
 * "By Continent" walks the world region by region. Which of them a given
 * pairing offers is decided by {@see forQuiz()}.
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
     * The order options offered for a pairing. Grouping by how flags look only
     * says something when the flags are on screen — as the question or as the
     * answer to pick from; otherwise the deck is ordered by region or not at
     * all.
     *
     * @return self[]
     */
    public static function forQuiz(Quiz $quiz): array
    {
        $showsFlags = $quiz->shows(Attribute::Flag) || $quiz->picksFlag();
        return $showsFlags
            ? [self::Random, self::Continent, self::Color, self::Shape, self::ShapeColor, self::Similarity]
            : [self::Random, self::Continent];
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
