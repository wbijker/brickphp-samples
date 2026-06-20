<?php

namespace Samples\FlagQuiz;

/**
 * The visual signature of a flag — its dominant {@see FlagShape} and the set of
 * {@see FlagColor}s it carries. This is what powers the "similar flags next to
 * each other" orderings on the start screen (see {@see FlagSort}).
 *
 * The classification is deliberately coarse and a touch subjective: every flag
 * gets ONE shape (the most salient layout) and the handful of colours the eye
 * registers, ignoring tiny details in emblems. It is a grouping aid, not an
 * exhaustive vexillological description.
 *
 * The catalogue is keyed by ISO-2 code and built once. A country with no entry
 * (should not happen for the bundled set) falls back to Other / no colours.
 */
final class FlagTraits
{
    /** @param FlagColor[] $colors */
    public function __construct(
        public readonly FlagShape $shape,
        public readonly array $colors,
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
        // row is [code, shape, 'colour-letters'] where the letters are the
        // FlagColor backing values: R O Y G B W K(black) M(maroon).
        $rows = [
            // --- Africa ---
            ['dz', FlagShape::Crescent, 'GWR'], ['ao', FlagShape::Emblem, 'RKY'], ['bj', FlagShape::Other, 'GYR'],
            ['bw', FlagShape::Horizontal, 'BWK'], ['bf', FlagShape::Horizontal, 'RGY'], ['bi', FlagShape::Diagonal, 'RWG'],
            ['cv', FlagShape::Horizontal, 'RYBW'], ['cm', FlagShape::Vertical, 'GRY'], ['cf', FlagShape::Other, 'RYGBW'],
            ['td', FlagShape::Vertical, 'BYR'], ['km', FlagShape::Crescent, 'RYGBW'], ['cg', FlagShape::Diagonal, 'GYR'],
            ['cd', FlagShape::Diagonal, 'RYB'], ['dj', FlagShape::Triangle, 'RGBW'], ['eg', FlagShape::Horizontal, 'RYWK'],
            ['gq', FlagShape::Triangle, 'RGBW'], ['er', FlagShape::Triangle, 'RYGB'], ['sz', FlagShape::Emblem, 'RYBK'],
            ['et', FlagShape::Horizontal, 'RYGB'], ['ga', FlagShape::Horizontal, 'YGB'], ['gm', FlagShape::Horizontal, 'RGBW'],
            ['gh', FlagShape::Horizontal, 'RYGK'], ['gn', FlagShape::Vertical, 'RYG'], ['gw', FlagShape::Other, 'RYGK'],
            ['ke', FlagShape::Emblem, 'RGWK'], ['ls', FlagShape::Horizontal, 'GBWK'], ['lr', FlagShape::Canton, 'RBW'],
            ['ly', FlagShape::Crescent, 'RGWK'], ['mg', FlagShape::Other, 'RGW'], ['mw', FlagShape::Horizontal, 'RGK'],
            ['ml', FlagShape::Vertical, 'GYR'], ['mr', FlagShape::Crescent, 'RYG'], ['mu', FlagShape::Horizontal, 'RYGB'],
            ['ma', FlagShape::Emblem, 'RG'], ['mz', FlagShape::Triangle, 'RYGWK'], ['na', FlagShape::Diagonal, 'RYGBW'],
            ['ne', FlagShape::Horizontal, 'OGW'], ['ng', FlagShape::Vertical, 'GW'], ['rw', FlagShape::Horizontal, 'YGB'],
            ['st', FlagShape::Triangle, 'RYGK'], ['sn', FlagShape::Vertical, 'RYG'], ['sc', FlagShape::Diagonal, 'RYGBW'],
            ['sl', FlagShape::Horizontal, 'GBW'], ['so', FlagShape::Emblem, 'BW'], ['za', FlagShape::Other, 'RYGBW'],
            ['ss', FlagShape::Triangle, 'RYGBWK'], ['sd', FlagShape::Triangle, 'RGWK'], ['tz', FlagShape::Diagonal, 'YGBK'],
            ['tg', FlagShape::Canton, 'RYGW'], ['tn', FlagShape::Crescent, 'RW'], ['ug', FlagShape::Emblem, 'RYWK'],
            ['zm', FlagShape::Emblem, 'ROGK'], ['zw', FlagShape::Triangle, 'RYGWK'],

            // --- Asia ---
            ['af', FlagShape::Emblem, 'RGWK'], ['am', FlagShape::Horizontal, 'ROB'], ['az', FlagShape::Crescent, 'RGBW'],
            ['bh', FlagShape::Other, 'RW'], ['bd', FlagShape::Disc, 'RG'], ['bt', FlagShape::Diagonal, 'OYW'],
            ['bn', FlagShape::Diagonal, 'RYWK'], ['kh', FlagShape::Emblem, 'RBW'], ['cn', FlagShape::Emblem, 'RY'],
            ['ge', FlagShape::Cross, 'RW'], ['in', FlagShape::Horizontal, 'OGBW'], ['id', FlagShape::Horizontal, 'RW'],
            ['ir', FlagShape::Horizontal, 'RGW'], ['iq', FlagShape::Horizontal, 'RGWK'], ['il', FlagShape::Emblem, 'BW'],
            ['jp', FlagShape::Disc, 'RW'], ['jo', FlagShape::Triangle, 'RGWK'], ['kz', FlagShape::Emblem, 'YB'],
            ['kw', FlagShape::Triangle, 'RGWK'], ['kg', FlagShape::Emblem, 'RY'], ['la', FlagShape::Disc, 'RBW'],
            ['lb', FlagShape::Emblem, 'RGW'], ['my', FlagShape::Canton, 'RYBW'], ['mv', FlagShape::Crescent, 'RGW'],
            ['mn', FlagShape::Vertical, 'RYB'], ['mm', FlagShape::Horizontal, 'RYGW'], ['np', FlagShape::Other, 'RBW'],
            ['kp', FlagShape::Horizontal, 'RBW'], ['om', FlagShape::Other, 'RGW'], ['pk', FlagShape::Crescent, 'GW'],
            ['ps', FlagShape::Triangle, 'RGWK'], ['ph', FlagShape::Triangle, 'RYBW'], ['qa', FlagShape::Other, 'WM'],
            ['sa', FlagShape::Emblem, 'GW'], ['sg', FlagShape::Crescent, 'RW'], ['kr', FlagShape::Emblem, 'RBWK'],
            ['lk', FlagShape::Emblem, 'ROYG'], ['sy', FlagShape::Horizontal, 'RGWK'], ['tw', FlagShape::Canton, 'RBW'],
            ['tj', FlagShape::Horizontal, 'RYGW'], ['th', FlagShape::Horizontal, 'RBW'], ['tl', FlagShape::Triangle, 'RYWK'],
            ['tr', FlagShape::Crescent, 'RW'], ['tm', FlagShape::Crescent, 'RGW'], ['ae', FlagShape::Other, 'RGWK'],
            ['uz', FlagShape::Horizontal, 'RGBW'], ['vn', FlagShape::Emblem, 'RY'], ['ye', FlagShape::Horizontal, 'RWK'],

            // --- Europe ---
            ['al', FlagShape::Emblem, 'RK'], ['ad', FlagShape::Vertical, 'RYB'], ['at', FlagShape::Horizontal, 'RW'],
            ['by', FlagShape::Horizontal, 'RGW'], ['be', FlagShape::Vertical, 'RYK'], ['ba', FlagShape::Triangle, 'YBW'],
            ['bg', FlagShape::Horizontal, 'RGW'], ['hr', FlagShape::Horizontal, 'RBW'], ['cy', FlagShape::Emblem, 'OGW'],
            ['cz', FlagShape::Triangle, 'RBW'], ['dk', FlagShape::Cross, 'RW'], ['ee', FlagShape::Horizontal, 'BWK'],
            ['fi', FlagShape::Cross, 'BW'], ['fr', FlagShape::Vertical, 'RBW'], ['de', FlagShape::Horizontal, 'RYK'],
            ['gr', FlagShape::Canton, 'BW'], ['hu', FlagShape::Horizontal, 'RGW'], ['is', FlagShape::Cross, 'RBW'],
            ['ie', FlagShape::Vertical, 'OGW'], ['it', FlagShape::Vertical, 'RGW'], ['xk', FlagShape::Emblem, 'YBW'],
            ['lv', FlagShape::Horizontal, 'WM'], ['li', FlagShape::Horizontal, 'RYB'], ['lt', FlagShape::Horizontal, 'RYG'],
            ['lu', FlagShape::Horizontal, 'RBW'], ['mt', FlagShape::Vertical, 'RW'], ['md', FlagShape::Vertical, 'RYB'],
            ['mc', FlagShape::Horizontal, 'RW'], ['me', FlagShape::Emblem, 'RY'], ['nl', FlagShape::Horizontal, 'RBW'],
            ['mk', FlagShape::Emblem, 'RY'], ['no', FlagShape::Cross, 'RBW'], ['pl', FlagShape::Horizontal, 'RW'],
            ['pt', FlagShape::Vertical, 'RYG'], ['ro', FlagShape::Vertical, 'RYB'], ['ru', FlagShape::Horizontal, 'RBW'],
            ['sm', FlagShape::Horizontal, 'YBW'], ['rs', FlagShape::Horizontal, 'RYBW'], ['sk', FlagShape::Horizontal, 'RBW'],
            ['si', FlagShape::Horizontal, 'RBW'], ['es', FlagShape::Horizontal, 'RY'], ['se', FlagShape::Cross, 'YB'],
            ['ch', FlagShape::Cross, 'RW'], ['ua', FlagShape::Horizontal, 'YB'], ['gb', FlagShape::Diagonal, 'RBW'],
            ['va', FlagShape::Vertical, 'YW'],

            // --- North America ---
            ['ag', FlagShape::Triangle, 'RYBWK'], ['bs', FlagShape::Triangle, 'YBK'], ['bb', FlagShape::Vertical, 'YBK'],
            ['bz', FlagShape::Emblem, 'RBW'], ['ca', FlagShape::Vertical, 'RW'], ['cr', FlagShape::Horizontal, 'RBW'],
            ['cu', FlagShape::Triangle, 'RBW'], ['dm', FlagShape::Cross, 'RYGWK'], ['do', FlagShape::Cross, 'RBW'],
            ['sv', FlagShape::Horizontal, 'YBW'], ['gd', FlagShape::Emblem, 'RYG'], ['gt', FlagShape::Vertical, 'BW'],
            ['ht', FlagShape::Horizontal, 'RBW'], ['hn', FlagShape::Horizontal, 'BW'], ['jm', FlagShape::Diagonal, 'YGK'],
            ['mx', FlagShape::Vertical, 'RGW'], ['ni', FlagShape::Horizontal, 'BW'], ['pa', FlagShape::Other, 'RBW'],
            ['kn', FlagShape::Diagonal, 'RYGWK'], ['lc', FlagShape::Triangle, 'YBWK'], ['vc', FlagShape::Vertical, 'YGB'],
            ['tt', FlagShape::Diagonal, 'RWK'], ['us', FlagShape::Canton, 'RBW'],

            // --- South America ---
            ['ar', FlagShape::Horizontal, 'YBW'], ['bo', FlagShape::Horizontal, 'RYG'], ['br', FlagShape::Emblem, 'YGB'],
            ['cl', FlagShape::Canton, 'RBW'], ['co', FlagShape::Horizontal, 'RYB'], ['ec', FlagShape::Horizontal, 'RYB'],
            ['gy', FlagShape::Triangle, 'RYGWK'], ['py', FlagShape::Horizontal, 'RBW'], ['pe', FlagShape::Vertical, 'RW'],
            ['sr', FlagShape::Horizontal, 'RYGW'], ['uy', FlagShape::Canton, 'YBW'], ['ve', FlagShape::Horizontal, 'RYB'],

            // --- Oceania ---
            ['au', FlagShape::Canton, 'RBW'], ['fj', FlagShape::Canton, 'RBW'], ['ki', FlagShape::Emblem, 'RYBW'],
            ['mh', FlagShape::Diagonal, 'OBW'], ['fm', FlagShape::Emblem, 'BW'], ['nr', FlagShape::Horizontal, 'YBW'],
            ['nz', FlagShape::Canton, 'RBW'], ['pw', FlagShape::Disc, 'YB'], ['pg', FlagShape::Diagonal, 'RYWK'],
            ['ws', FlagShape::Canton, 'RBW'], ['sb', FlagShape::Diagonal, 'YGBW'], ['to', FlagShape::Canton, 'RW'],
            ['tv', FlagShape::Canton, 'RYBW'], ['vu', FlagShape::Other, 'RYGK'],
        ];

        $letters = [];
        foreach (FlagColor::cases() as $color) {
            $letters[$color->value] = $color;
        }

        $cache = [];
        foreach ($rows as [$code, $shape, $colorLetters]) {
            $colors = [];
            foreach (str_split($colorLetters) as $letter) {
                $colors[] = $letters[$letter];
            }
            $cache[$code] = new self($shape, $colors);
        }
        return $cache;
    }
}
