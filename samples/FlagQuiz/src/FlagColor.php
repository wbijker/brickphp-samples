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
}
