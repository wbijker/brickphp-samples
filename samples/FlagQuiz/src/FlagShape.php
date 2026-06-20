<?php

namespace Samples\FlagQuiz;

/**
 * The dominant visual layout of a flag — the single most salient pattern (a flag
 * can technically have several, we pick the one the eye reads first). Used by the
 * "By Shape" ordering on the start screen to sit lookalike layouts together.
 */
enum FlagShape: string
{
    case Horizontal = 'horizontal'; // horizontal bands / stripes
    case Vertical = 'vertical';     // vertical bands
    case Cross = 'cross';           // Nordic-style cross
    case Diagonal = 'diagonal';     // saltires, diagonal divisions, Union Jacks
    case Triangle = 'triangle';     // hoist triangle (Sudan, Czechia, …)
    case Canton = 'canton';         // stripes + a corner canton (ensigns, USA)
    case Disc = 'disc';             // a single central disc (Japan, Bangladesh)
    case Crescent = 'crescent';     // crescent moon (Turkey, Pakistan, …)
    case Emblem = 'emblem';         // a field dominated by a central emblem/seal
    case Other = 'other';           // anything that does not fit the above

    /** Human-readable name, used in the missed-flags hints (not yet surfaced). */
    public function label(): string
    {
        return match ($this) {
            self::Horizontal => 'Horizontal stripes',
            self::Vertical => 'Vertical stripes',
            self::Cross => 'Cross',
            self::Diagonal => 'Diagonal',
            self::Triangle => 'Hoist triangle',
            self::Canton => 'Canton',
            self::Disc => 'Central disc',
            self::Crescent => 'Crescent',
            self::Emblem => 'Central emblem',
            self::Other => 'Other',
        };
    }
}
