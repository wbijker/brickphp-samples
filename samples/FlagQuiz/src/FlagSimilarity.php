<?php

namespace Samples\FlagQuiz;

/**
 * Hand-curated "look-alike" groups: flags that are easy to confuse for one
 * another, clustered together so the "By Similarity" ordering ({@see FlagSort})
 * sits them side by side. This is a subjective, human-eye grouping — unlike the
 * mechanical {@see FlagTraits} shape/colour signature — so it lives in its own
 * catalogue keyed by ISO-2 code.
 *
 * Countries with no obvious twin fall into {@see FlagSimilarity::OTHER_GROUP},
 * which always sorts last.
 */
final class FlagSimilarity
{
    /** Group number used for every flag that isn't in a curated cluster. */
    public const OTHER_GROUP = 999;

    /** The curated group number for a country code, or OTHER_GROUP. */
    public static function groupFor(string $code): int
    {
        return self::map()[strtolower($code)] ?? self::OTHER_GROUP;
    }

    /**
     * code => group number, built once from the grouped lists below.
     *
     * @return array<string, int>
     */
    private static function map(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        // Each row is a cluster of look-alikes (by ISO-2 code). The row's
        // position is its group number (1-based), which is all the ordering
        // needs — within a group the deck stays shuffled, like the other modes.
        $groups = [
            // 1 — green/yellow/red verticals & horizontals + the blue/yellow/red trio
            ['bj', 'gn', 'cm', 'ml', 'gw', 'gh', 'td', 'sn', 'et', 'bo', 'ad', 'md', 'ro', 'ec', 'co', 'lt', 've', 'am', 'mm'],
            // 2 — red field with a centred emblem / crescent / star
            ['kg', 'tn', 'ma', 'vn', 'cn', 'tr', 'al', 'ws', 'tw', 'mk', 'me', 'mv'],
            // 3 — pan-Arab black/white/green with a red hoist triangle (+ kin)
            ['ps', 'kw', 'jo', 'ae', 'ss', 'sd', 'sy', 'bg'],
            // 4 — red/white/black horizontal with a central emblem
            ['iq', 'ye', 'eg'],
            // 5 — diagonal stripe across the field
            ['kn', 'tz', 'cg', 'cd', 'na', 'tt', 'sb', 'mh'],
            // 6 — vertical/horizontal red-white-blue with a hoist accent
            ['nl', 'fr', 'cz', 'ph', 'lu'],
            // 7 — red/white bicolours (+ the red-white-red tribands)
            ['mt', 'mc', 'at', 'sg', 'pl', 'id', 'lv', 'no', 'pe', 'dk', 'ca', 'lb'],
            // 8 — white/maroon serrated
            ['bh', 'qa'],
            // 9 — single central disc
            ['bd', 'pw', 'jp', 'la'],
            // 10 — Nordic / off-centre crosses
            ['fi', 'se', 'ge', 'is'],
            // 11 — green-white-red verticals (+ Niger/India orange-white-green)
            ['mx', 'it', 'ie', 'ng', 'ne', 'in'],
            // 12 — green/white/red horizontals with an emblem
            ['ir', 'tj', 'hu', 'gq'],
            // 13 — quartered with a central emblem
            ['do', 'pa'],
            // 14 — stars-and-stripes family (stripes + a corner canton)
            ['us', 'my', 'lr', 'tg'],
            // 15 — blue/white/blue horizontal with a central emblem (+ light-blue/star kin)
            ['ni', 'sv', 'hn', 'gt', 'ar', 'uz', 'ee', 'bw', 'so', 'kz', 'fm'],
            // 16 — red/white/blue horizontal triband
            ['th', 'cr'],
            // 17 — blue/white stripes with a canton emblem
            ['uy', 'gr', 'cu'],
            // 18 — black/yellow/red
            ['be', 'de'],
            // 19 — blue ensign, Union Jack + Southern Cross
            ['tv', 'nz', 'fj', 'au'],
            // 20 — green field with a white emblem
            ['zm', 'tm', 'sa'],
            // 21 — green field with a crescent
            ['dz', 'pk', 'mr'],
            // 22 — pan-Slavic white/blue/red (+ kin)
            ['ru', 'sk', 'si', 'py', 'cl', 'rs', 'hr'],
            // 23 — three horizontal bands with a centred emblem/animal
            ['ls', 'sl', 'ga', 'az', 'rw', 'gm', 'ke'],
            // 24 — complex multi-colour designs with a hoist triangle
            ['dj', 'er', 'zw', 'km', 'gy', 'za', 'mz', 'st', 'bs', 'tl'],
        ];

        $cache = [];
        foreach ($groups as $i => $codes) {
            foreach ($codes as $code) {
                $cache[$code] = $i + 1;
            }
        }
        return $cache;
    }
}
