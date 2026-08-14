<?php

namespace Samples\FlagQuiz;

/**
 * The full definition of a flag's appearance: its dominant {@see FlagShape}, the
 * set of {@see FlagColor}s it carries, and the hand-curated look-alike group it
 * belongs to. All three drive the "similar flags next to each other" orderings
 * on the start screen (see {@see FlagSort}).
 *
 * Shape/colour are a deliberately coarse, mechanical signature (one shape, the
 * handful of colours the eye registers). The similarity group is the subjective,
 * human-eye clustering of flags that are genuinely easy to confuse — group 0
 * meaning "no obvious twin". The catalogue is keyed by ISO-2 code and built once.
 */
final class FlagTraits
{
    /** Group number for a flag with no curated look-alike group. */
    public const NO_GROUP = 0;

    /**
     * @param FlagColor[] $colors the flag's colours in *band order* — top to
     *   bottom, or hoist to fly for a vertically divided flag — with the field
     *   first and any charge/emblem colours after it. {@see colorKey()} sorts
     *   them canonically for grouping, so this order is free to be the visual
     *   one; {@see \Samples\FlagQuiz\Components\WorldMap} paints it straight
     *   onto the country's outline.
     */
    public function __construct(
        public readonly FlagShape $shape,
        public readonly array $colors,
        public readonly int $similarityGroup = self::NO_GROUP,
    ) {}

    /**
     * True when this flag's bands run hoist→fly rather than top→bottom, so a
     * fill painted from {@see $colors} matches the real flag's direction.
     */
    public function bandsAreVertical(): bool
    {
        return $this->shape === FlagShape::Vertical;
    }

    /** Traits for a country code, or a neutral fallback when unknown. */
    public static function for(string $code): self
    {
        return self::map()[strtolower($code)] ?? new self(FlagShape::Other, []);
    }

    /**
     * Canonical palette key: the colours in {@see FlagColor} declaration order,
     * concatenated. Band order is intentionally ignored so that, e.g., the Dutch
     * (red-white-blue) and Russian (white-blue-red) palettes collapse to the same
     * key and group together under "By Color".
     */
    public function colorKey(): string
    {
        $key = '';
        foreach (FlagColor::cases() as $color) {
            if (in_array($color, $this->colors, true)) {
                $key .= $color->value;
            }
        }
        return $key;
    }

    /**
     * Sortable key that clusters look-alikes for the "By Similarity" ordering.
     * Zero-padded so group 2 sorts before group 10; ungrouped flags ({@see
     * NO_GROUP}) are pushed to the end.
     */
    public function similarityKey(): string
    {
        return sprintf('%03d', $this->similarityGroup === self::NO_GROUP ? 999 : $this->similarityGroup);
    }

    /**
     * The full catalogue, built once and cached.
     *
     * @return array<string, self>
     */
    private static function map(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        // Grouped by continent (matching Country::all()) for auditability. Each
        // row is [code, shape, 'colour-letters', similarityGroup] where the
        // letters are the FlagColor backing values (R O Y G B W K(black)
        // M(maroon)) and the group is the look-alike cluster (0 = no twin).
        //
        // The letters read in BAND ORDER — top to bottom, or hoist to fly for a
        // vertically divided flag — field first, charge/emblem colours last. So
        // 'BWR' is France (blue|white|red) and 'RWB' the Netherlands
        // (red/white/blue). Grouping never depends on this order: colorKey()
        // re-sorts into FlagColor declaration order, which is exactly what makes
        // those two share a palette key.
        $rows = [
            // --- Africa ---
            ['dz', FlagShape::Crescent, 'GWR', 21], ['ao', FlagShape::Emblem, 'RKY', 0], ['bj', FlagShape::Other, 'GYR', 1],
            ['bw', FlagShape::Horizontal, 'BWK', 15], ['bf', FlagShape::Horizontal, 'RGY', 0], ['bi', FlagShape::Diagonal, 'RWG', 0],
            ['cv', FlagShape::Horizontal, 'BWRY', 0], ['cm', FlagShape::Vertical, 'GRY', 1], ['cf', FlagShape::Other, 'BWGYR', 0],
            ['td', FlagShape::Vertical, 'BYR', 1], ['km', FlagShape::Crescent, 'YWRBG', 24], ['cd', FlagShape::Diagonal, 'BRY', 5],
            ['dj', FlagShape::Triangle, 'BGWR', 24], ['eg', FlagShape::Horizontal, 'RWKY', 4], ['gq', FlagShape::Triangle, 'GWRB', 12],
            ['er', FlagShape::Triangle, 'RGBY', 24], ['sz', FlagShape::Emblem, 'BYRK', 0], ['et', FlagShape::Horizontal, 'GYRB', 1],
            ['ga', FlagShape::Horizontal, 'GYB', 23], ['gm', FlagShape::Horizontal, 'RWBG', 23], ['gh', FlagShape::Horizontal, 'RYGK', 1],
            ['gn', FlagShape::Vertical, 'RYG', 1], ['gw', FlagShape::Other, 'RYGK', 1], ['ci', FlagShape::Vertical, 'OWG', 11],
            ['ke', FlagShape::Emblem, 'KWRG', 23],
            ['ls', FlagShape::Horizontal, 'BWGK', 23], ['lr', FlagShape::Canton, 'RWB', 14], ['ly', FlagShape::Crescent, 'RKGW', 0],
            ['mg', FlagShape::Other, 'WRG', 0], ['mw', FlagShape::Horizontal, 'KRG', 0], ['ml', FlagShape::Vertical, 'GYR', 1],
            ['mr', FlagShape::Crescent, 'RGY', 21], ['mu', FlagShape::Horizontal, 'RBYG', 0], ['ma', FlagShape::Emblem, 'RG', 2],
            ['mz', FlagShape::Triangle, 'GWKYR', 24], ['na', FlagShape::Diagonal, 'BWRGY', 5], ['ne', FlagShape::Horizontal, 'OWG', 11],
            ['ng', FlagShape::Vertical, 'GW', 11], ['cg', FlagShape::Diagonal, 'GYR', 5], ['rw', FlagShape::Horizontal, 'BYG', 23],
            ['st', FlagShape::Triangle, 'GYRK', 24], ['sn', FlagShape::Vertical, 'GYR', 1], ['sc', FlagShape::Diagonal, 'BYRWG', 0],
            ['sl', FlagShape::Horizontal, 'GWB', 23], ['so', FlagShape::Emblem, 'BW', 15], ['za', FlagShape::Other, 'RWGYB', 24],
            ['ss', FlagShape::Triangle, 'KRWGBY', 3], ['sd', FlagShape::Triangle, 'RWKG', 3], ['tz', FlagShape::Diagonal, 'GYKB', 5],
            ['tg', FlagShape::Canton, 'GYRW', 14], ['tn', FlagShape::Crescent, 'RW', 2], ['ug', FlagShape::Emblem, 'KYRW', 0],
            ['zm', FlagShape::Emblem, 'GRKO', 20], ['zw', FlagShape::Triangle, 'GYRKW', 24],
            // --- Asia ---
            ['af', FlagShape::Emblem, 'KRGW', 0], ['am', FlagShape::Horizontal, 'RBO', 1], ['az', FlagShape::Crescent, 'BRGW', 23],
            ['bh', FlagShape::Other, 'WR', 8], ['bd', FlagShape::Disc, 'GR', 9], ['bt', FlagShape::Diagonal, 'YOW', 0],
            ['bn', FlagShape::Diagonal, 'YWKR', 0], ['kh', FlagShape::Emblem, 'BRW', 0], ['cn', FlagShape::Emblem, 'RY', 2],
            ['ge', FlagShape::Cross, 'WR', 10], ['in', FlagShape::Horizontal, 'OWGB', 11], ['id', FlagShape::Horizontal, 'RW', 7],
            ['ir', FlagShape::Horizontal, 'GWR', 12], ['iq', FlagShape::Horizontal, 'RWKG', 4], ['il', FlagShape::Emblem, 'WB', 0],
            ['jp', FlagShape::Disc, 'WR', 9], ['jo', FlagShape::Triangle, 'KWGR', 3], ['kz', FlagShape::Emblem, 'BY', 15],
            ['kw', FlagShape::Triangle, 'GWRK', 3], ['kg', FlagShape::Emblem, 'RY', 2], ['la', FlagShape::Disc, 'RBW', 9],
            ['lb', FlagShape::Emblem, 'RWG', 7], ['my', FlagShape::Canton, 'RWBY', 14], ['mv', FlagShape::Crescent, 'RGW', 2],
            ['mn', FlagShape::Vertical, 'RBY', 0], ['mm', FlagShape::Horizontal, 'YGRW', 1], ['np', FlagShape::Other, 'RBW', 0],
            ['kp', FlagShape::Horizontal, 'BWR', 0], ['om', FlagShape::Other, 'RWG', 0], ['pk', FlagShape::Crescent, 'WG', 21],
            ['ps', FlagShape::Triangle, 'KWGR', 3], ['ph', FlagShape::Triangle, 'BRWY', 6], ['qa', FlagShape::Other, 'WM', 8],
            ['sa', FlagShape::Emblem, 'GW', 20], ['sg', FlagShape::Crescent, 'RW', 7], ['kr', FlagShape::Emblem, 'WRBK', 0],
            ['lk', FlagShape::Emblem, 'GORY', 0], ['sy', FlagShape::Horizontal, 'RWKG', 3], ['tw', FlagShape::Canton, 'RBW', 2],
            ['tj', FlagShape::Horizontal, 'RWGY', 12], ['th', FlagShape::Horizontal, 'RBW', 16], ['tl', FlagShape::Triangle, 'RYKW', 24],
            ['tr', FlagShape::Crescent, 'RW', 2], ['tm', FlagShape::Crescent, 'GRW', 20], ['ae', FlagShape::Other, 'RGWK', 3],
            ['uz', FlagShape::Horizontal, 'BWGR', 15], ['vn', FlagShape::Emblem, 'RY', 2], ['ye', FlagShape::Horizontal, 'RWK', 4],
            // --- Europe ---
            ['al', FlagShape::Emblem, 'RK', 2], ['ad', FlagShape::Vertical, 'BYR', 1], ['at', FlagShape::Horizontal, 'RW', 7],
            ['by', FlagShape::Horizontal, 'RGW', 0], ['be', FlagShape::Vertical, 'KYR', 18], ['ba', FlagShape::Triangle, 'BYW', 0],
            ['bg', FlagShape::Horizontal, 'WGR', 3], ['hr', FlagShape::Horizontal, 'RWB', 22], ['cy', FlagShape::Emblem, 'WOG', 0],
            ['cz', FlagShape::Triangle, 'WRB', 6], ['dk', FlagShape::Cross, 'RW', 7], ['ee', FlagShape::Horizontal, 'BKW', 15],
            ['fi', FlagShape::Cross, 'WB', 10], ['fr', FlagShape::Vertical, 'BWR', 6], ['de', FlagShape::Horizontal, 'KRY', 18],
            ['gr', FlagShape::Canton, 'BW', 17], ['hu', FlagShape::Horizontal, 'RWG', 12], ['is', FlagShape::Cross, 'BWR', 10],
            ['ie', FlagShape::Vertical, 'GWO', 11], ['it', FlagShape::Vertical, 'GWR', 11], ['xk', FlagShape::Emblem, 'BYW', 0],
            ['lv', FlagShape::Horizontal, 'MW', 7], ['li', FlagShape::Horizontal, 'BRY', 0], ['lt', FlagShape::Horizontal, 'YGR', 1],
            ['lu', FlagShape::Horizontal, 'RWB', 6], ['mt', FlagShape::Vertical, 'WR', 7], ['md', FlagShape::Vertical, 'BYR', 1],
            ['mc', FlagShape::Horizontal, 'RW', 7], ['me', FlagShape::Emblem, 'RY', 2], ['nl', FlagShape::Horizontal, 'RWB', 6],
            ['mk', FlagShape::Emblem, 'RY', 2], ['no', FlagShape::Cross, 'RWB', 7], ['pl', FlagShape::Horizontal, 'WR', 7],
            ['pt', FlagShape::Vertical, 'GRY', 0], ['ro', FlagShape::Vertical, 'BYR', 1], ['ru', FlagShape::Horizontal, 'WBR', 22],
            ['sm', FlagShape::Horizontal, 'WBY', 0], ['rs', FlagShape::Horizontal, 'RBWY', 22], ['sk', FlagShape::Horizontal, 'WBR', 22],
            ['si', FlagShape::Horizontal, 'WBR', 22], ['es', FlagShape::Horizontal, 'RY', 0], ['se', FlagShape::Cross, 'BY', 10],
            ['ch', FlagShape::Cross, 'RW', 0], ['ua', FlagShape::Horizontal, 'BY', 0], ['gb', FlagShape::Diagonal, 'BWR', 0],
            ['va', FlagShape::Vertical, 'YW', 0],
            // --- North America ---
            ['ag', FlagShape::Triangle, 'RKYWB', 0], ['bs', FlagShape::Triangle, 'BYK', 24], ['bb', FlagShape::Vertical, 'BYK', 0],
            ['bz', FlagShape::Emblem, 'BRW', 0], ['ca', FlagShape::Vertical, 'RW', 7], ['cr', FlagShape::Horizontal, 'BWR', 16],
            ['cu', FlagShape::Triangle, 'BWR', 17], ['dm', FlagShape::Cross, 'GYKWR', 0], ['do', FlagShape::Cross, 'BRW', 13],
            ['sv', FlagShape::Horizontal, 'BWY', 15], ['gd', FlagShape::Emblem, 'RYG', 0], ['gt', FlagShape::Vertical, 'BW', 15],
            ['ht', FlagShape::Horizontal, 'BRW', 0], ['hn', FlagShape::Horizontal, 'BW', 15], ['jm', FlagShape::Diagonal, 'GYK', 0],
            ['mx', FlagShape::Vertical, 'GWR', 11], ['ni', FlagShape::Horizontal, 'BW', 15], ['pa', FlagShape::Other, 'WRB', 13],
            ['kn', FlagShape::Diagonal, 'GYKWR', 5], ['lc', FlagShape::Triangle, 'BWKY', 0], ['vc', FlagShape::Vertical, 'BYG', 0],
            ['tt', FlagShape::Diagonal, 'RWK', 5], ['us', FlagShape::Canton, 'RWB', 14],
            // --- South America ---
            ['ar', FlagShape::Horizontal, 'BWY', 15], ['bo', FlagShape::Horizontal, 'RYG', 1], ['br', FlagShape::Emblem, 'GYB', 0],
            ['cl', FlagShape::Canton, 'WRB', 22], ['co', FlagShape::Horizontal, 'YBR', 1], ['ec', FlagShape::Horizontal, 'YBR', 1],
            ['gy', FlagShape::Triangle, 'GWYKR', 24], ['py', FlagShape::Horizontal, 'RWB', 22], ['pe', FlagShape::Vertical, 'RW', 7],
            ['sr', FlagShape::Horizontal, 'GWRY', 0], ['uy', FlagShape::Canton, 'WBY', 17], ['ve', FlagShape::Horizontal, 'YBR', 1],
            // --- Oceania ---
            ['au', FlagShape::Canton, 'BWR', 19], ['fj', FlagShape::Canton, 'BWR', 19], ['ki', FlagShape::Emblem, 'RYBW', 0],
            ['mh', FlagShape::Diagonal, 'BWO', 5], ['fm', FlagShape::Emblem, 'BW', 15], ['nr', FlagShape::Horizontal, 'BYW', 0],
            ['nz', FlagShape::Canton, 'BWR', 19], ['pw', FlagShape::Disc, 'BY', 9], ['pg', FlagShape::Diagonal, 'RYKW', 0],
            ['ws', FlagShape::Canton, 'RBW', 2], ['sb', FlagShape::Diagonal, 'BYGW', 5], ['to', FlagShape::Canton, 'RW', 0],
            ['tv', FlagShape::Canton, 'BWRY', 19], ['vu', FlagShape::Other, 'RGKY', 0],
        ];

        $letters = [];
        foreach (FlagColor::cases() as $color) {
            $letters[$color->value] = $color;
        }

        $cache = [];
        foreach ($rows as [$code, $shape, $colorLetters, $group]) {
            $colors = [];
            foreach (str_split($colorLetters) as $letter) {
                $colors[] = $letters[$letter];
            }
            $cache[$code] = new self($shape, $colors, $group);
        }
        return $cache;
    }
}
