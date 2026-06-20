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

    /** @param FlagColor[] $colors */
    public function __construct(
        public readonly FlagShape $shape,
        public readonly array $colors,
        public readonly int $similarityGroup = self::NO_GROUP,
    ) {}

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
        $rows = [
            // --- Africa ---
            ['dz', FlagShape::Crescent, 'GWR', 21], ['ao', FlagShape::Emblem, 'RKY', 0], ['bj', FlagShape::Other, 'GYR', 1],
            ['bw', FlagShape::Horizontal, 'BWK', 15], ['bf', FlagShape::Horizontal, 'RGY', 0], ['bi', FlagShape::Diagonal, 'RWG', 0],
            ['cv', FlagShape::Horizontal, 'RYBW', 0], ['cm', FlagShape::Vertical, 'GRY', 1], ['cf', FlagShape::Other, 'RYGBW', 0],
            ['td', FlagShape::Vertical, 'BYR', 1], ['km', FlagShape::Crescent, 'RYGBW', 24], ['cd', FlagShape::Diagonal, 'RYB', 5],
            ['dj', FlagShape::Triangle, 'RGBW', 24], ['eg', FlagShape::Horizontal, 'RYWK', 4], ['gq', FlagShape::Triangle, 'RGBW', 12],
            ['er', FlagShape::Triangle, 'RYGB', 24], ['sz', FlagShape::Emblem, 'RYBK', 0], ['et', FlagShape::Horizontal, 'RYGB', 1],
            ['ga', FlagShape::Horizontal, 'YGB', 23], ['gm', FlagShape::Horizontal, 'RGBW', 23], ['gh', FlagShape::Horizontal, 'RYGK', 1],
            ['gn', FlagShape::Vertical, 'RYG', 1], ['gw', FlagShape::Other, 'RYGK', 1], ['ke', FlagShape::Emblem, 'RGWK', 23],
            ['ls', FlagShape::Horizontal, 'GBWK', 23], ['lr', FlagShape::Canton, 'RBW', 14], ['ly', FlagShape::Crescent, 'RGWK', 0],
            ['mg', FlagShape::Other, 'RGW', 0], ['mw', FlagShape::Horizontal, 'RGK', 0], ['ml', FlagShape::Vertical, 'GYR', 1],
            ['mr', FlagShape::Crescent, 'RYG', 21], ['mu', FlagShape::Horizontal, 'RYGB', 0], ['ma', FlagShape::Emblem, 'RG', 2],
            ['mz', FlagShape::Triangle, 'RYGWK', 24], ['na', FlagShape::Diagonal, 'RYGBW', 5], ['ne', FlagShape::Horizontal, 'OGW', 11],
            ['ng', FlagShape::Vertical, 'GW', 11], ['cg', FlagShape::Diagonal, 'GYR', 5], ['rw', FlagShape::Horizontal, 'YGB', 23],
            ['st', FlagShape::Triangle, 'RYGK', 24], ['sn', FlagShape::Vertical, 'RYG', 1], ['sc', FlagShape::Diagonal, 'RYGBW', 0],
            ['sl', FlagShape::Horizontal, 'GBW', 23], ['so', FlagShape::Emblem, 'BW', 15], ['za', FlagShape::Other, 'RYGBW', 24],
            ['ss', FlagShape::Triangle, 'RYGBWK', 3], ['sd', FlagShape::Triangle, 'RGWK', 3], ['tz', FlagShape::Diagonal, 'YGBK', 5],
            ['tg', FlagShape::Canton, 'RYGW', 14], ['tn', FlagShape::Crescent, 'RW', 2], ['ug', FlagShape::Emblem, 'RYWK', 0],
            ['zm', FlagShape::Emblem, 'ROGK', 20], ['zw', FlagShape::Triangle, 'RYGWK', 24],
            // --- Asia ---
            ['af', FlagShape::Emblem, 'RGWK', 0], ['am', FlagShape::Horizontal, 'ROB', 1], ['az', FlagShape::Crescent, 'RGBW', 23],
            ['bh', FlagShape::Other, 'RW', 8], ['bd', FlagShape::Disc, 'RG', 9], ['bt', FlagShape::Diagonal, 'OYW', 0],
            ['bn', FlagShape::Diagonal, 'RYWK', 0], ['kh', FlagShape::Emblem, 'RBW', 0], ['cn', FlagShape::Emblem, 'RY', 2],
            ['ge', FlagShape::Cross, 'RW', 10], ['in', FlagShape::Horizontal, 'OGBW', 11], ['id', FlagShape::Horizontal, 'RW', 7],
            ['ir', FlagShape::Horizontal, 'RGW', 12], ['iq', FlagShape::Horizontal, 'RGWK', 4], ['il', FlagShape::Emblem, 'BW', 0],
            ['jp', FlagShape::Disc, 'RW', 9], ['jo', FlagShape::Triangle, 'RGWK', 3], ['kz', FlagShape::Emblem, 'YB', 15],
            ['kw', FlagShape::Triangle, 'RGWK', 3], ['kg', FlagShape::Emblem, 'RY', 2], ['la', FlagShape::Disc, 'RBW', 9],
            ['lb', FlagShape::Emblem, 'RGW', 7], ['my', FlagShape::Canton, 'RYBW', 14], ['mv', FlagShape::Crescent, 'RGW', 2],
            ['mn', FlagShape::Vertical, 'RYB', 0], ['mm', FlagShape::Horizontal, 'RYGW', 1], ['np', FlagShape::Other, 'RBW', 0],
            ['kp', FlagShape::Horizontal, 'RBW', 0], ['om', FlagShape::Other, 'RGW', 0], ['pk', FlagShape::Crescent, 'GW', 21],
            ['ps', FlagShape::Triangle, 'RGWK', 3], ['ph', FlagShape::Triangle, 'RYBW', 6], ['qa', FlagShape::Other, 'WM', 8],
            ['sa', FlagShape::Emblem, 'GW', 20], ['sg', FlagShape::Crescent, 'RW', 7], ['kr', FlagShape::Emblem, 'RBWK', 0],
            ['lk', FlagShape::Emblem, 'ROYG', 0], ['sy', FlagShape::Horizontal, 'RGWK', 3], ['tw', FlagShape::Canton, 'RBW', 2],
            ['tj', FlagShape::Horizontal, 'RYGW', 12], ['th', FlagShape::Horizontal, 'RBW', 16], ['tl', FlagShape::Triangle, 'RYWK', 24],
            ['tr', FlagShape::Crescent, 'RW', 2], ['tm', FlagShape::Crescent, 'RGW', 20], ['ae', FlagShape::Other, 'RGWK', 3],
            ['uz', FlagShape::Horizontal, 'RGBW', 15], ['vn', FlagShape::Emblem, 'RY', 2], ['ye', FlagShape::Horizontal, 'RWK', 4],
            // --- Europe ---
            ['al', FlagShape::Emblem, 'RK', 2], ['ad', FlagShape::Vertical, 'RYB', 1], ['at', FlagShape::Horizontal, 'RW', 7],
            ['by', FlagShape::Horizontal, 'RGW', 0], ['be', FlagShape::Vertical, 'RYK', 18], ['ba', FlagShape::Triangle, 'YBW', 0],
            ['bg', FlagShape::Horizontal, 'RGW', 3], ['hr', FlagShape::Horizontal, 'RBW', 22], ['cy', FlagShape::Emblem, 'OGW', 0],
            ['cz', FlagShape::Triangle, 'RBW', 6], ['dk', FlagShape::Cross, 'RW', 7], ['ee', FlagShape::Horizontal, 'BWK', 15],
            ['fi', FlagShape::Cross, 'BW', 10], ['fr', FlagShape::Vertical, 'RBW', 6], ['de', FlagShape::Horizontal, 'RYK', 18],
            ['gr', FlagShape::Canton, 'BW', 17], ['hu', FlagShape::Horizontal, 'RGW', 12], ['is', FlagShape::Cross, 'RBW', 10],
            ['ie', FlagShape::Vertical, 'OGW', 11], ['it', FlagShape::Vertical, 'RGW', 11], ['xk', FlagShape::Emblem, 'YBW', 0],
            ['lv', FlagShape::Horizontal, 'WM', 7], ['li', FlagShape::Horizontal, 'RYB', 0], ['lt', FlagShape::Horizontal, 'RYG', 1],
            ['lu', FlagShape::Horizontal, 'RBW', 6], ['mt', FlagShape::Vertical, 'RW', 7], ['md', FlagShape::Vertical, 'RYB', 1],
            ['mc', FlagShape::Horizontal, 'RW', 7], ['me', FlagShape::Emblem, 'RY', 2], ['nl', FlagShape::Horizontal, 'RBW', 6],
            ['mk', FlagShape::Emblem, 'RY', 2], ['no', FlagShape::Cross, 'RBW', 7], ['pl', FlagShape::Horizontal, 'RW', 7],
            ['pt', FlagShape::Vertical, 'RYG', 0], ['ro', FlagShape::Vertical, 'RYB', 1], ['ru', FlagShape::Horizontal, 'RBW', 22],
            ['sm', FlagShape::Horizontal, 'YBW', 0], ['rs', FlagShape::Horizontal, 'RYBW', 22], ['sk', FlagShape::Horizontal, 'RBW', 22],
            ['si', FlagShape::Horizontal, 'RBW', 22], ['es', FlagShape::Horizontal, 'RY', 0], ['se', FlagShape::Cross, 'YB', 10],
            ['ch', FlagShape::Cross, 'RW', 0], ['ua', FlagShape::Horizontal, 'YB', 0], ['gb', FlagShape::Diagonal, 'RBW', 0],
            ['va', FlagShape::Vertical, 'YW', 0],
            // --- North America ---
            ['ag', FlagShape::Triangle, 'RYBWK', 0], ['bs', FlagShape::Triangle, 'YBK', 24], ['bb', FlagShape::Vertical, 'YBK', 0],
            ['bz', FlagShape::Emblem, 'RBW', 0], ['ca', FlagShape::Vertical, 'RW', 7], ['cr', FlagShape::Horizontal, 'RBW', 16],
            ['cu', FlagShape::Triangle, 'RBW', 17], ['dm', FlagShape::Cross, 'RYGWK', 0], ['do', FlagShape::Cross, 'RBW', 13],
            ['sv', FlagShape::Horizontal, 'YBW', 15], ['gd', FlagShape::Emblem, 'RYG', 0], ['gt', FlagShape::Vertical, 'BW', 15],
            ['ht', FlagShape::Horizontal, 'RBW', 0], ['hn', FlagShape::Horizontal, 'BW', 15], ['jm', FlagShape::Diagonal, 'YGK', 0],
            ['mx', FlagShape::Vertical, 'RGW', 11], ['ni', FlagShape::Horizontal, 'BW', 15], ['pa', FlagShape::Other, 'RBW', 13],
            ['kn', FlagShape::Diagonal, 'RYGWK', 5], ['lc', FlagShape::Triangle, 'YBWK', 0], ['vc', FlagShape::Vertical, 'YGB', 0],
            ['tt', FlagShape::Diagonal, 'RWK', 5], ['us', FlagShape::Canton, 'RBW', 14],
            // --- South America ---
            ['ar', FlagShape::Horizontal, 'YBW', 15], ['bo', FlagShape::Horizontal, 'RYG', 1], ['br', FlagShape::Emblem, 'YGB', 0],
            ['cl', FlagShape::Canton, 'RBW', 22], ['co', FlagShape::Horizontal, 'RYB', 1], ['ec', FlagShape::Horizontal, 'RYB', 1],
            ['gy', FlagShape::Triangle, 'RYGWK', 24], ['py', FlagShape::Horizontal, 'RBW', 22], ['pe', FlagShape::Vertical, 'RW', 7],
            ['sr', FlagShape::Horizontal, 'RYGW', 0], ['uy', FlagShape::Canton, 'YBW', 17], ['ve', FlagShape::Horizontal, 'RYB', 1],
            // --- Oceania ---
            ['au', FlagShape::Canton, 'RBW', 19], ['fj', FlagShape::Canton, 'RBW', 19], ['ki', FlagShape::Emblem, 'RYBW', 0],
            ['mh', FlagShape::Diagonal, 'OBW', 5], ['fm', FlagShape::Emblem, 'BW', 15], ['nr', FlagShape::Horizontal, 'YBW', 0],
            ['nz', FlagShape::Canton, 'RBW', 19], ['pw', FlagShape::Disc, 'YB', 9], ['pg', FlagShape::Diagonal, 'RYWK', 0],
            ['ws', FlagShape::Canton, 'RBW', 2], ['sb', FlagShape::Diagonal, 'YGBW', 5], ['to', FlagShape::Canton, 'RW', 0],
            ['tv', FlagShape::Canton, 'RYBW', 19], ['vu', FlagShape::Other, 'RYGK', 0],
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
