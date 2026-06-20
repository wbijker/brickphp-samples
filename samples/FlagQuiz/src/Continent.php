<?php

namespace Samples\FlagQuiz;

/**
 * The six inhabited continents. Each {@see Country} belongs to one, and the
 * start screen lets the player restrict a game to a chosen subset. Persisted as
 * session state (string-backed, so it round-trips through SessionStateManager).
 */
enum Continent: string
{
    case Africa = 'africa';
    case Asia = 'asia';
    case Europe = 'europe';
    case NorthAmerica = 'north-america';
    case SouthAmerica = 'south-america';
    case Oceania = 'oceania';

    /** Human-readable name for the picker chip. */
    public function label(): string
    {
        return match ($this) {
            self::Africa => 'Africa',
            self::Asia => 'Asia',
            self::Europe => 'Europe',
            self::NorthAmerica => 'North America',
            self::SouthAmerica => 'South America',
            self::Oceania => 'Oceania',
        };
    }
}
