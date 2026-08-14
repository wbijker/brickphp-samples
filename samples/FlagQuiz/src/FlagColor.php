<?php

namespace Samples\FlagQuiz;

/**
 * The dominant colours that appear on a flag. Each {@see FlagTraits} carries the
 * set its country's flag uses; the "By Color" ordering on the start screen groups
 * flags that share a palette next to each other.
 *
 * The single-letter backing value keeps the {@see FlagTraits} catalogue compact,
 * and the case *declaration order* doubles as the canonical sort order so that,
 * e.g., red-white-blue and blue-white-red flags produce the same palette key and
 * land together.
 */
enum FlagColor: string
{
    case Red = 'R';
    case Orange = 'O';
    case Yellow = 'Y';
    case Green = 'G';
    case Blue = 'B';
    case White = 'W';
    case Black = 'K';
    case Maroon = 'M';

    /**
     * A representative ink for this colour, used to paint a country's outline
     * with its flag's bands on the Explore map. Deliberately mid-range rather
     * than any one flag's exact shade — these stand in for ~200 flags, and the
     * bands have to stay legible at a few pixels across on a pale basemap.
     * White is warmed slightly off-pure so a band still reads as a band.
     */
    public function hex(): string
    {
        return match ($this) {
            self::Red => '#d7222a',
            self::Orange => '#f07d1a',
            self::Yellow => '#f7cb15',
            self::Green => '#15914f',
            self::Blue => '#14539a',
            self::White => '#f2efe6',
            self::Black => '#1f1d1b',
            self::Maroon => '#8c1b3f',
        };
    }
}
