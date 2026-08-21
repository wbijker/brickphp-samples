<?php

/**
 * Finds the shape of every river, lake and mountain range in
 * {@see CountryFacts} and writes them to src/PlaceShapes.php, so the map can
 * trace the Zambezi rather than shade in Zambia.
 *
 * The shapes come from Natural Earth — river centrelines, lakes, and the
 * `Range/mtn` polygons of its geography regions. Those files run to megabytes
 * and hold thousands of features the quiz never asks about, so the matching
 * happens here, once, and only the shapes the catalogue names are committed.
 *
 * Matching is by name, which is the hard part: Natural Earth calls the Amazon
 * "Amazonas", the Blue Nile "Abay" and the Euphrates "Al Furat", abbreviates
 * every range to "Mts.", and carries local names for half of Europe. Three
 * passes handle it — the name as written, the name with its classifying words
 * dropped ("Lake Kariba" and "Kariba Dam" both being about Kariba), and a
 * containment check for the rest. What none of them match simply gets no
 * entry, and the map falls back to highlighting the country.
 *
 * The shapes are thinned on the way out: at the zoom a country is looked at,
 * points a few kilometres apart are the same point, and keeping all of them
 * would put megabytes of coastline into a PHP file to no visible effect.
 *
 * Run from this directory:  php generate-places.php
 */

require __DIR__ . '/../src/Continent.php';
require __DIR__ . '/../src/CountryFacts.php';
require __DIR__ . '/../src/Country.php';
require __DIR__ . '/../src/Quiz.php';
require __DIR__ . '/../src/Attribute.php';

use Samples\FlagQuiz\Attribute;
use Samples\FlagQuiz\Country;

const BASE = 'https://cdn.jsdelivr.net/gh/nvkelso/natural-earth-vector@v5.1.2/geojson/';

/**
 * How far a point may sit from the line drawn without it, in degrees, before
 * it is worth keeping. Roughly two kilometres at the equator — below what a
 * country-sized view can show.
 */
const SIMPLIFY = 0.04;

/**
 * The classifying words a place-name is topped and tailed with. Dropped for
 * matching, so "Lake Kariba", "Kariba Dam" and "Kariba" are one thing, and
 * Natural Earth's "Lebombo Mts." meets the catalogue's "Lubombo Mountains".
 */
const GENERIC = [
    'mount', 'mt', 'mts', 'mtn', 'mtns', 'mountain', 'mountains', 'range', 'ranges',
    'massif', 'highlands', 'plateau', 'escarpment', 'lake', 'lac', 'lago', 'loch',
    'lough', 'reservoir', 'river', 'dam', 'sea', 'national', 'park', 'hills',
];

/**
 * Names Natural Earth files under something else entirely — a different
 * language, or a stretch of the river rather than the river. Only where no
 * amount of normalising would meet in the middle.
 */
const ALIASES = [
    'amazon' => 'amazonas',
    'blue nile' => 'abay',
    'euphrates' => 'al furat',
    'dnieper' => 'dnipro',
    'irrawaddy' => 'ayeyarwady',
    'rio grande' => 'rio bravo del norte',
    'yellow river' => 'huang he',
    'yangtze' => 'chang jiang',
    'tigris' => 'dijlah',
    'mekong' => 'lancang jiang',
    'ganges' => 'ganga',
    'saint lawrence' => 'st lawrence',
    'meuse' => 'maas',
    'scheldt' => 'schelde',
    'western dvina' => 'zapadnaya dvina',
    'moselle' => 'mosel',
    'sea of galilee' => 'lake tiberias',
    'dead sea' => 'dead sea',
    'lake nyasa' => 'lake malawi',
    'lake niassa' => 'lake malawi',
];

// ------------------------------------------------------------------
// Names
// ------------------------------------------------------------------

/** The catalogue's normalisation, so keys here are keys the app can look up by. */
function nameKey(string $name): string
{
    return Country::normalize($name);
}

/** The name with its classifying words dropped — what two spellings share. */
function core(string $name): string
{
    $words = array_filter(
        explode(' ', nameKey($name)),
        fn(string $w) => $w !== '' && !in_array($w, GENERIC, true),
    );
    $core = implode(' ', $words);
    return $core === '' ? nameKey($name) : $core;
}

// ------------------------------------------------------------------
// Natural Earth
// ------------------------------------------------------------------

function fetchGeoJson(string $file): array
{
    $cache = sys_get_temp_dir() . '/vexi-' . $file;
    if (!is_file($cache)) {
        fwrite(STDERR, "fetching " . $file . "…\n");
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => BASE . $file,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_USERAGENT => 'vexi-place-generator/1.0 (BrickPHP sample app)',
        ]);
        $body = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        if ($body === false || $status !== 200) {
            fwrite(STDERR, "could not fetch $file (HTTP $status)\n");
            exit(1);
        }
        file_put_contents($cache, $body);
    }
    return json_decode(file_get_contents($cache), true);
}

/**
 * Every feature of a file indexed by the names it goes by — both as written
 * and cored — so a catalogue name can be looked for either way. A name shared
 * by several features keeps all of them: the Nile is a dozen segments, and
 * drawing one of them is drawing a twelfth of the Nile.
 *
 * @param string[] $nameProps
 * @return array{exact: array<string, array>, core: array<string, array>}
 */
function indexFeatures(array $geo, array $nameProps, ?callable $keep = null): array
{
    $exact = [];
    $cores = [];
    foreach ($geo['features'] as $feature) {
        $props = $feature['properties'];
        if ($keep !== null && !$keep($props)) {
            continue;
        }
        $seen = [];
        foreach ($nameProps as $prop) {
            $value = $props[$prop] ?? null;
            if (!$value) {
                continue;
            }
            foreach (preg_split('/[|,]/', $value) as $part) {
                $k = nameKey($part);
                if ($k === '' || isset($seen[$k])) {
                    continue;
                }
                $seen[$k] = true;
                $exact[$k][] = $feature;
                $cores[core($part)][] = $feature;
            }
        }
    }
    return ['exact' => $exact, 'core' => $cores];
}

/**
 * The features for a catalogue name: as written, then cored, then by
 * containment — "Rila" against "Rila Mountains", which neither of the first
 * two would catch. Containment is held to names of four characters or more,
 * below which it matches half the world.
 */
function findFeatures(string $name, array $index): array
{
    $k = nameKey($name);
    $alias = ALIASES[$k] ?? null;
    foreach (array_filter([$k, $alias]) as $candidate) {
        if (isset($index['exact'][$candidate])) {
            return $index['exact'][$candidate];
        }
    }
    $c = core($name);
    if ($c !== '' && isset($index['core'][$c])) {
        return $index['core'][$c];
    }
    if ($alias !== null && isset($index['core'][core($alias)])) {
        return $index['core'][core($alias)];
    }
    if (mb_strlen($c) >= 4) {
        foreach ($index['core'] as $known => $features) {
            if ($known === $c) {
                return $features;
            }
            if (str_contains($known, $c) || str_contains($c, $known)) {
                // Guard against a short known name swallowing a long one:
                // "ural" should not answer for "Ural Mountains" via "Urals"
                // *and* for everything else containing those four letters.
                if (abs(mb_strlen($known) - mb_strlen($c)) <= 8) {
                    return $features;
                }
            }
        }
    }
    return [];
}

// ------------------------------------------------------------------
// Where the countries are, so a match can be checked against them
// ------------------------------------------------------------------

/**
 * How far outside a country's box a shape may sit and still be taken as
 * belonging to it, in degrees. Loose on purpose: this is not measuring
 * whether a river is in a country, it is throwing out the ones on another
 * continent.
 */
const BOX_SLACK_DEG = 1.5;

/**
 * Each country's bounding boxes, one per landmass, as
 * [minLat, minLon, maxLat, maxLon].
 *
 * Boxes rather than outlines because of what they are for. A name matched by
 * spelling alone lands on the wrong side of the world often enough to matter —
 * China's Pearl River finding Mississippi's, Jamaica's Rio Grande finding the
 * one on the Mexican border — and telling those from the real thing does not
 * need the coastline, it needs to know they are eight thousand kilometres out.
 * Per landmass so an overseas territory does not stretch one box across an
 * ocean and wave everything through.
 *
 * @return array<string, array<int, array{0: float, 1: float, 2: float, 3: float}>>
 */
function countryBoxes(): array
{
    $geo = fetchGeoJson('ne_50m_admin_0_countries.geojson');
    $boxes = [];
    foreach ($geo['features'] as $feature) {
        $props = $feature['properties'];
        // ISO_A2 is '-99' for a handful (France, Norway, Kosovo…); ISO_A2_EH
        // carries the code for those.
        $code = strtolower($props['ISO_A2_EH'] ?? $props['ISO_A2'] ?? '');
        if ($code === '' || $code === '-99') {
            continue;
        }
        foreach (runs($feature['geometry'] ?? []) as $ring) {
            $lats = array_column($ring, 0);
            $lons = array_column($ring, 1);
            $boxes[$code][] = [min($lats), min($lons), max($lats), max($lons)];
        }
    }
    return $boxes;
}

/** Whether any point of the shape falls in any of these boxes. */
function touchesBoxes(array $paths, array $boxes): bool
{
    foreach ($paths as $path) {
        foreach ($path as [$lat, $lon]) {
            foreach ($boxes as [$minLat, $minLon, $maxLat, $maxLon]) {
                if ($lat >= $minLat - BOX_SLACK_DEG && $lat <= $maxLat + BOX_SLACK_DEG
                    && $lon >= $minLon - BOX_SLACK_DEG && $lon <= $maxLon + BOX_SLACK_DEG) {
                    return true;
                }
            }
        }
    }
    return false;
}

// ------------------------------------------------------------------
// Geometry
// ------------------------------------------------------------------

/**
 * A feature's coordinate runs as [latitude, longitude] pairs — the order
 * Leaflet wants, which is the reverse of GeoJSON's.
 *
 * @return array<int, array<int, array{0: float, 1: float}>>
 */
function runs(array $geometry): array
{
    $type = $geometry['type'] ?? '';
    $coords = $geometry['coordinates'] ?? [];
    $out = match ($type) {
        'LineString' => [$coords],
        'MultiLineString' => $coords,
        // Outer rings only: a lake's islands are not what is being pointed at.
        'Polygon' => [$coords[0] ?? []],
        'MultiPolygon' => array_map(fn(array $polygon) => $polygon[0] ?? [], $coords),
        default => [],
    };
    return array_values(array_filter(array_map(
        fn(array $run) => array_map(fn(array $point) => [round($point[1], 4), round($point[0], 4)], $run),
        $out,
    ), fn(array $run) => count($run) >= 2));
}

/**
 * Ramer–Douglas–Peucker: drop the points that sit close enough to the line
 * their neighbours already describe.
 */
function simplify(array $points, float $tolerance): array
{
    if (count($points) < 3) {
        return $points;
    }
    $first = 0;
    $last = count($points) - 1;
    $worst = 0.0;
    $at = 0;
    for ($i = $first + 1; $i < $last; $i++) {
        $d = perpendicular($points[$i], $points[$first], $points[$last]);
        if ($d > $worst) {
            $worst = $d;
            $at = $i;
        }
    }
    if ($worst <= $tolerance) {
        return [$points[$first], $points[$last]];
    }
    $left = simplify(array_slice($points, 0, $at + 1), $tolerance);
    $right = simplify(array_slice($points, $at), $tolerance);
    return array_merge(array_slice($left, 0, -1), $right);
}

/** Distance from a point to the line through two others, in degrees. */
function perpendicular(array $p, array $a, array $b): float
{
    [$py, $px] = $p;
    [$ay, $ax] = $a;
    [$by, $bx] = $b;
    $dx = $bx - $ax;
    $dy = $by - $ay;
    $len = sqrt($dx * $dx + $dy * $dy);
    if ($len < 1e-12) {
        return sqrt(($px - $ax) ** 2 + ($py - $ay) ** 2);
    }
    return abs($dy * $px - $dx * $py + $bx * $ay - $by * $ax) / $len;
}

// ------------------------------------------------------------------
// Walk the catalogue
// ------------------------------------------------------------------

$sources = [
    Attribute::River->value => [
        'file' => 'ne_10m_rivers_lake_centerlines.geojson',
        'props' => ['name', 'name_en', 'name_alt'],
        'keep' => null,
    ],
    Attribute::Lake->value => [
        'file' => 'ne_10m_lakes.geojson',
        'props' => ['name', 'name_en', 'name_alt', 'dam_name'],
        'keep' => null,
    ],
    Attribute::Mountains->value => [
        'file' => 'ne_10m_geography_regions_polys.geojson',
        'props' => ['NAME', 'NAME_EN', 'NAMEALT'],
        'keep' => fn(array $props) => str_contains($props['FEATURECLA'] ?? '', 'Range')
            || str_contains($props['FEATURECLA'] ?? '', 'Plateau')
            || str_contains($props['FEATURECLA'] ?? '', 'Foothills'),
    ],
];

$boxes = countryBoxes();
$attributes = [Attribute::River, Attribute::Lake, Attribute::Mountains];
$shapes = [];
$report = [];

foreach ($attributes as $attribute) {
    $spec = $sources[$attribute->value];
    $index = indexFeatures(fetchGeoJson($spec['file']), $spec['props'], $spec['keep']);

    // Keyed by country as well as name, because a name is only worth trusting
    // against the country that used it. Jamaica's Rio Grande and the one on
    // the Mexican border are the same three words and four thousand miles
    // apart; pooling the two countries' claims lets the wrong river answer for
    // both. Asked country by country, Jamaica finds nothing and is left with
    // its country highlighted, which is the right answer.
    $found = 0;
    $missing = [];
    $rejected = [];
    foreach (Country::all() as $country) {
        $claimed = $boxes[$country->code] ?? [];
        foreach ($attribute->valuesOf($country) as $name) {
            $where = $country->name . ': ' . $name;
            $features = findFeatures($name, $index);
            if ($features === []) {
                $missing[] = $where;
                continue;
            }

            $paths = [];
            $elsewhere = 0;
            foreach ($features as $feature) {
                $runs = [];
                foreach (runs($feature['geometry'] ?? []) as $run) {
                    $thinned = simplify($run, SIMPLIFY);
                    if (count($thinned) >= 2) {
                        $runs[] = $thinned;
                    }
                }
                if ($runs === []) {
                    continue;
                }
                // A feature that never comes near this country is a different
                // thing that happens to share the name.
                if ($claimed !== [] && !touchesBoxes($runs, $claimed)) {
                    $elsewhere++;
                    continue;
                }
                foreach ($runs as $run) {
                    $paths[] = $run;
                }
            }

            if ($paths === []) {
                $missing[] = $where;
                if ($elsewhere > 0) {
                    $rejected[] = $where;
                }
                continue;
            }
            $shapes[$attribute->value][$country->code . '|' . nameKey($name)] = $paths;
            $found++;
        }
    }

    $total = $found + count($missing);
    $report[$attribute->value] = [
        'total' => $total,
        'found' => $found,
        'missing' => $missing,
        'rejected' => $rejected,
    ];
    printf(
        "%-10s %3d of %3d drawn (%d rejected as a different place of the same name)\n",
        $attribute->value, $found, $total, count($rejected),
    );
}

// ------------------------------------------------------------------
// Write it out
// ------------------------------------------------------------------

$rows = '';
$points = 0;
foreach ($shapes as $kind => $byName) {
    $rows .= "            '{$kind}' => [\n";
    foreach ($byName as $name => $paths) {
        $encoded = [];
        foreach ($paths as $path) {
            $points += count($path);
            $encoded[] = '[' . implode(',', array_map(
                fn(array $p) => '[' . $p[0] . ',' . $p[1] . ']',
                $path,
            )) . ']';
        }
        $rows .= "                '" . str_replace("'", "\\'", $name) . "' => ["
            . implode(',', $encoded) . "],\n";
    }
    $rows .= "            ],\n";
}

$file = <<<PHP
<?php

namespace Samples\FlagQuiz;

/**
 * The shape of every river, lake and mountain range the catalogue names — what
 * the map traces when it is asked to show the Zambezi rather than Zambia.
 *
 * Generated by tools/generate-places.php from Natural Earth, matched to the
 * names in {@see CountryFacts} and thinned to the detail a country-sized view
 * can show. Keyed by the catalogue's own name, normalised: the fuzzy part of
 * the matching — Natural Earth's local names, its abbreviations, its habit of
 * breaking one river into a dozen segments — happened once, in the generator,
 * so a lookup here is exact.
 *
 * Not everything is in it. Natural Earth has no geometry for many of the dams
 * and reservoirs, and its mountain ranges are the coarse label polygons of its
 * geography regions rather than true outlines, so a good share of the
 * catalogue finds nothing. That is not an error: a place with no shape here
 * falls back to the map highlighting its country, which is what the map did
 * before any of this. Hand-editing is pointless; regenerate it if the
 * catalogue changes.
 *
 * Coordinates are [latitude, longitude] — Leaflet's order, not GeoJSON's.
 */
final class PlaceShapes
{
    /**
     * The paths making up this place, or none if it has no shape. Several
     * paths is the normal case rather than the exception: a river arrives as
     * the string of segments it was surveyed in, and a range as the massifs
     * it is made of.
     *
     * @return array<int, array<int, array{0: float, 1: float}>>
     */
    public static function for(Attribute \$attribute, string \$code, string \$name): array
    {
        \$key = strtolower(\$code) . '|' . Country::normalize(\$name);
        return self::map()[\$attribute->value][\$key] ?? [];
    }

    /** Whether this place can be drawn at all. */
    public static function has(Attribute \$attribute, string \$code, string \$name): bool
    {
        return self::for(\$attribute, \$code, \$name) !== [];
    }

    /** @return array<string, array<string, array>> */
    private static function map(): array
    {
        static \$cache = null;
        \$cache ??= [
{$rows}        ];
        return \$cache;
    }
}

PHP;

file_put_contents(__DIR__ . '/../src/PlaceShapes.php', $file);

printf("\nwrote %d points across %d places\n", $points, array_sum(array_map(fn($r) => $r['found'], $report)));
foreach ($report as $kind => $r) {
    printf("\n%s — %d without a shape:\n  %s\n", $kind, count($r['missing']), implode(', ', $r['missing']));
}
