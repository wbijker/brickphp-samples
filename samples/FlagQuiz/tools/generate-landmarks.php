<?php

/**
 * Finds where each landmark in {@see CountryFacts} actually is, and a picture
 * of it, and writes the answers to src/LandmarkPlaces.php.
 *
 * Wikipedia carries both things a pin needs — an article's coordinates and its
 * lead image — and is asked for them in two passes.
 *
 * The first pass asks by title, fifty landmarks to a request. Most of them are
 * named here the way Wikipedia names them, so most are answered at once and
 * cheaply. The second pass takes what is left to the search API, one landmark
 * at a time, with the country's name added to the query — because a landmark's
 * name alone is often not unique, and "Cotton Tree" on its own finds a tree in
 * Brazil where "Cotton Tree Sierra Leone" finds the one on the coat of arms.
 *
 * Two passes rather than searching for everything because search is the
 * expensive query and Wikipedia rations it accordingly: six hundred searches
 * gets throttled to a halt within a couple of countries, while six hundred
 * titles is fourteen requests it serves without complaint.
 *
 * Every answer is then checked against the country's real borders — the same
 * Natural Earth polygons the quiz map draws — and thrown away if it lands
 * outside them. Wikipedia's top hit is usually right and occasionally a
 * different thing of the same name on another continent; a landmark with no
 * coordinates inside its own country is better left unplaced than pinned in
 * the wrong hemisphere, and the map falls back to highlighting the country.
 *
 * Run from this directory:  php generate-landmarks.php
 *
 * Naming country codes limits it to those and prints the answers without
 * writing anything — `php generate-landmarks.php fr eg` to see what France and
 * Egypt would get, which is how to check a change before spending six hundred
 * requests on it.
 *
 * It is a one-off: the results are committed, so the app never calls
 * Wikipedia. Re-run it when the landmark catalogue changes.
 */

require __DIR__ . '/../src/Continent.php';
require __DIR__ . '/../src/CountryFacts.php';
require __DIR__ . '/../src/Country.php';
require __DIR__ . '/../src/Quiz.php';
require __DIR__ . '/../src/Attribute.php';

use Samples\FlagQuiz\Country;

/** Where the quiz map's country shapes come from — the same ones, so the check matches what is drawn. */
const GEOJSON_URL =
    'https://cdn.jsdelivr.net/gh/nvkelso/natural-earth-vector@v5.1.2/geojson/ne_50m_admin_0_countries.geojson';

/** How many of Wikipedia's hits to consider before giving up on a landmark. */
const CANDIDATES = 3;

/**
 * How far outside its country a landmark may sit and still be believed, in
 * kilometres.
 *
 * The borders being checked against are the 50m generalisation the map draws,
 * which rounds off exactly the places landmarks like to be: Freetown is on a
 * peninsula the outline cuts away, and the Vatican is smaller than the width
 * of the line. Without an allowance those come back "not in their own
 * country". With one, a hit on the wrong continent is still thousands of
 * kilometres out and still thrown away, which is the case this check exists
 * for.
 */
const BORDER_SLACK_KM = 60.0;

/**
 * A landmark's answer: where it is and what it looks like. Written out as
 * constructor calls, so the generated file is the same shape as the rest of
 * the catalogues.
 */
final class Place
{
    public function __construct(
        public readonly string $code,
        public readonly string $landmark,
        public readonly float $lat,
        public readonly float $lon,
        public readonly string $image,
        public readonly string $article,
    ) {}
}

// ------------------------------------------------------------------
// The country shapes, so an answer can be checked against its borders
// ------------------------------------------------------------------

/** @return array<string, array> ISO-2 => the country's polygon rings */
function countryShapes(): array
{
    $cache = sys_get_temp_dir() . '/vexi-ne-50m-countries.json';
    if (!is_file($cache)) {
        fwrite(STDERR, "fetching country shapes…\n");
        // curl rather than file_get_contents: a PHP built without the https
        // stream wrapper still has curl, and this tool already needs it.
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => GEOJSON_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_USERAGENT => 'vexi-landmark-generator/1.0 (BrickPHP sample app)',
        ]);
        $body = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        if ($body === false || $status !== 200) {
            fwrite(STDERR, "could not fetch the country shapes (HTTP $status)\n");
            exit(1);
        }
        file_put_contents($cache, $body);
    }

    $geo = json_decode(file_get_contents($cache), true);
    $shapes = [];
    foreach ($geo['features'] as $feature) {
        $props = $feature['properties'];
        // ISO_A2 is '-99' for a handful (France, Norway, Kosovo…); the
        // ISO_A2_EH field carries the code for those.
        $code = $props['ISO_A2_EH'] ?? $props['ISO_A2'] ?? '';
        $code = strtolower($code);
        if ($code === '' || $code === '-99') {
            continue;
        }
        $geometry = $feature['geometry'];
        $polygons = $geometry['type'] === 'Polygon' ? [$geometry['coordinates']] : $geometry['coordinates'];
        foreach ($polygons as $polygon) {
            $shapes[$code][] = $polygon[0];   // outer ring only; holes don't matter here
        }
    }
    return $shapes;
}

/**
 * Whether the point belongs to this country: inside its borders, or close
 * enough outside them that the outline's coarseness is the likelier
 * explanation ({@see BORDER_SLACK_KM}).
 */
function belongsTo(float $lat, float $lon, array $rings): bool
{
    return inside($lat, $lon, $rings) || nearestKm($lat, $lon, $rings) <= BORDER_SLACK_KM;
}

/**
 * Ray casting: whether the point falls inside any of the country's rings.
 * Coordinates are [lon, lat] the GeoJSON way round.
 */
function inside(float $lat, float $lon, array $rings): bool
{
    foreach ($rings as $ring) {
        $in = false;
        $n = count($ring);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$xi, $yi] = $ring[$i];
            [$xj, $yj] = $ring[$j];
            if (($yi > $lat) !== ($yj > $lat)
                && $lon < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi) {
                $in = !$in;
            }
        }
        if ($in) {
            return true;
        }
    }
    return false;
}

/**
 * Kilometres from the point to the nearest corner of the country's outline.
 * Corners rather than edges: an outline has enough of them that the difference
 * is far below the slack being measured against, and it keeps this a loop over
 * points instead of a projection.
 */
function nearestKm(float $lat, float $lon, array $rings): float
{
    $best = INF;
    foreach ($rings as $ring) {
        foreach ($ring as [$x, $y]) {
            $d = haversineKm($lat, $lon, $y, $x);
            if ($d < $best) {
                $best = $d;
            }
        }
    }
    return $best;
}

function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $r = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

// ------------------------------------------------------------------
// Wikipedia
// ------------------------------------------------------------------

/** How many titles one batched request asks about. The API's own limit. */
const BATCH = 50;

/**
 * Coordinates and pictures for a batch of landmark names, looked up as article
 * titles. Redirects are followed, so "Louvre" finds "Louvre" and "Angkor Wat"
 * finds "Angkor Wat" whatever the canonical spelling is.
 *
 * Keyed by the name as asked, not as Wikipedia normalised it — the caller
 * knows the landmark by the former. Names Wikipedia has no article for simply
 * do not appear.
 *
 * @param string[] $titles
 * @return ?array<string, array{title: string, lat: ?float, lon: ?float, image: ?string}>
 */
function lookupTitles(array $titles): ?array
{
    $body = ask([
        'action' => 'query',
        'format' => 'json',
        'formatversion' => 2,
        'titles' => implode('|', $titles),
        'redirects' => 1,
        'prop' => 'coordinates|pageimages',
        'piprop' => 'thumbnail',
        'pithumbsize' => 320,
    ], implode(', ', array_slice($titles, 0, 2)) . '…');
    if ($body === null) {
        return null;
    }

    // Wikipedia reports how it rewrote what was asked for: capitalisation and
    // whitespace under `normalized`, redirects under `redirects`. Both have to
    // be walked back to return answers under the names that were asked.
    $asked = [];
    foreach ($body['query']['normalized'] ?? [] as $n) {
        $asked[$n['to']] = $n['from'];
    }
    foreach ($body['query']['redirects'] ?? [] as $r) {
        $asked[$r['to']] = $asked[$r['from']] ?? $r['from'];
    }

    $out = [];
    foreach ($body['query']['pages'] ?? [] as $page) {
        if ($page['missing'] ?? false) {
            continue;
        }
        $title = $page['title'] ?? '';
        $coords = $page['coordinates'][0] ?? null;
        $thumb = $page['thumbnail']['source'] ?? null;
        $out[$asked[$title] ?? $title] = [
            'title' => $title,
            'lat' => isset($coords['lat']) ? (float)$coords['lat'] : null,
            'lon' => isset($coords['lon']) ? (float)$coords['lon'] : null,
            'image' => $thumb !== null ? strtok($thumb, '?') : null,
        ];
    }
    return $out;
}

/**
 * One request to the API, retried through throttling. Null means it never got
 * an answer — which is a different thing from an answer of "nothing", and
 * worth reporting rather than quietly recording as a landmark that cannot be
 * placed.
 */
function ask(array $query, string $what): ?array
{
    static $curl = null;
    $curl ??= curl_init();

    pace();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://en.wikipedia.org/w/api.php?' . http_build_query($query),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        // Wikipedia asks that tools identify themselves.
        CURLOPT_USERAGENT => 'vexi-landmark-generator/1.0 (BrickPHP sample app)',
    ]);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $body = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        if ($body !== false && $status === 200) {
            return json_decode($body, true);
        }
        // 429 means throttled, and Wikipedia says for how long when it can.
        // Its window is far longer than a polite pause between requests, so
        // the wait here is in tens of seconds rather than ones.
        $retryAfter = (int)(curl_getinfo($curl, CURLINFO_RETRY_AFTER) ?: 0);
        $wait = max($retryAfter, 15 * $attempt);
        fwrite(STDERR, sprintf(
            "    HTTP %d for \"%s\" — waiting %ds (try %d of 5)\n", $status, $what, $wait, $attempt,
        ));
        sleep($wait);
    }
    return null;
}

/**
 * The best few articles for a landmark, each with whatever coordinates and
 * lead image it carries. Null — as against an empty list — means the ask
 * itself failed, which is a different thing from Wikipedia having nothing:
 * the first is worth reporting and retrying, the second is just a landmark
 * it does not know.
 *
 * Asked slowly on purpose. Six hundred requests off the back of each other
 * gets throttled a couple of countries in, and a throttled request that
 * quietly returns nothing looks exactly like a landmark that cannot be
 * placed — which is how the first run of this came back with Peru knowing
 * where Machu Picchu is and not the Nazca Lines.
 *
 * @return ?array<int, array{title: string, lat: ?float, lon: ?float, image: ?string}>
 */
function lookup(string $landmark, string $country): ?array
{
    $body = ask([
        'action' => 'query',
        'format' => 'json',
        'formatversion' => 2,
        'generator' => 'search',
        'gsrsearch' => $landmark . ' ' . $country,
        'gsrlimit' => CANDIDATES,
        'prop' => 'coordinates|pageimages',
        'piprop' => 'thumbnail',
        'pithumbsize' => 320,
    ], $landmark);
    if ($body === null) {
        return null;
    }

    $pages = $body['query']['pages'] ?? [];
    // Search order, which the API reports as `index`.
    usort($pages, fn($a, $b) => ($a['index'] ?? 99) <=> ($b['index'] ?? 99));
    $out = [];
    foreach ($pages as $page) {
        $coords = $page['coordinates'][0] ?? null;
        $thumb = $page['thumbnail']['source'] ?? null;
        $out[] = [
            'title' => $page['title'] ?? '',
            'lat' => isset($coords['lat']) ? (float)$coords['lat'] : null,
            'lon' => isset($coords['lon']) ? (float)$coords['lon'] : null,
            // The API tacks its own tracking parameters onto the thumbnail;
            // the picture is the same without them.
            'image' => $thumb !== null ? strtok($thumb, '?') : null,
        ];
    }
    return $out;
}

/**
 * Hold the request rate to something Wikipedia is happy to serve — a pause
 * measured from the last call rather than a blanket sleep, so the time spent
 * checking borders counts towards it.
 */
function pace(): void
{
    static $last = 0.0;
    // A second apart. Faster than this and the anonymous rate limit starts
    // returning 429s a couple of countries in; at this rate the whole
    // catalogue takes about eleven minutes and nothing gets throttled, which
    // for something run once is the right trade.
    $gap = 1.0;
    $wait = $gap - (microtime(true) - $last);
    if ($wait > 0) {
        usleep((int)($wait * 1_000_000));
    }
    $last = microtime(true);
}

// ------------------------------------------------------------------
// Walk the catalogue
// ------------------------------------------------------------------

// Codes named on the command line run as a dry run over just those; no
// arguments means the whole catalogue, written out at the end.
$only = array_map('strtolower', array_slice($argv, 1));
$dryRun = $only !== [];

$places = [];
$unplaced = [];
$failed = [];
$noImage = [];
$total = 0;

$shapes = countryShapes();

$countries = $only === []
    ? Country::all()
    : array_filter(Country::all(), fn(Country $c) => in_array($c->code, $only, true));

/**
 * What a previous run already worked out, so a re-run costs nothing for the
 * landmarks it has already placed. Six hundred throttled searches is two hours
 * of somebody's afternoon, and most re-runs are because the catalogue changed
 * by a dozen entries.
 *
 * `--fresh` ignores it and asks Wikipedia about everything again.
 */
$reuse = [];
$fresh = in_array('--fresh', $argv, true);
$only = array_values(array_filter($only, fn(string $a) => $a !== '--fresh'));
$existing = __DIR__ . '/../src/LandmarkPlaces.php';
if (!$fresh && is_file($existing)) {
    require_once __DIR__ . '/../src/LandmarkPlace.php';
    require_once $existing;
    foreach (Country::all() as $country) {
        foreach ($country->facts()->landmarks as $landmark) {
            $place = \Samples\FlagQuiz\LandmarkPlaces::for($country->code, $landmark);
            if ($place !== null) {
                $reuse[$country->code . '|' . $landmark] = $place;
            }
        }
    }
    fwrite(STDERR, sprintf("reusing %d landmarks already placed\n", count($reuse)));
}

/** Every landmark to place, as [country, landmark]. */
$wanted = [];
foreach ($countries as $country) {
    foreach ($country->facts()->landmarks as $landmark) {
        $total++;
        $known = $reuse[$country->code . '|' . $landmark] ?? null;
        if ($known !== null) {
            $places[] = new Place(
                $country->code,
                $landmark,
                $known->lat,
                $known->lon,
                $known->image,
                '',
            );
            continue;
        }
        $wanted[] = [$country, $landmark];
    }
}

/**
 * Take an answer if it is one: coordinates that fall in the country it is
 * supposed to be in. Records the placement and says whether it took.
 */
$accept = function (Country $country, string $landmark, ?array $candidate) use (&$places, &$noImage, $shapes): bool {
    if ($candidate === null || $candidate['lat'] === null) {
        return false;
    }
    $rings = $shapes[$country->code] ?? [];
    // No shapes for this country — take the coordinates on trust, there is
    // nothing to check them against.
    if ($rings !== [] && !belongsTo($candidate['lat'], $candidate['lon'], $rings)) {
        return false;
    }
    if ($candidate['image'] === null) {
        $noImage[] = $country->name . ': ' . $landmark;
    }
    $places[] = new Place(
        $country->code,
        $landmark,
        round($candidate['lat'], 5),
        round($candidate['lon'], 5),
        $candidate['image'] ?? '',
        $candidate['title'],
    );
    printf("  %-3s %-42s %9.4f %10.4f  %s\n",
        $country->code, mb_substr($landmark, 0, 42), $candidate['lat'], $candidate['lon'],
        $candidate['image'] === null ? '(no picture)' : '');
    return true;
};

// ---- first pass: by title, fifty at a time ----
fwrite(STDERR, sprintf("asking by title (%d landmarks, %d requests)…\n", $total, (int)ceil($total / BATCH)));
$leftover = [];
foreach (array_chunk($wanted, BATCH) as $chunk) {
    $answers = lookupTitles(array_map(fn($w) => $w[1], $chunk));
    foreach ($chunk as [$country, $landmark]) {
        // A failed request leaves every landmark in the batch to the second
        // pass rather than writing them all off.
        if (!$accept($country, $landmark, $answers[$landmark] ?? null)) {
            $leftover[] = [$country, $landmark];
        }
    }
}

// ---- second pass: search, for whatever the titles missed ----
if ($leftover !== []) {
    fwrite(STDERR, sprintf("\nsearching for the remaining %d…\n", count($leftover)));
}
foreach ($leftover as [$country, $landmark]) {
    $candidates = lookup($landmark, $country->name);
    if ($candidates === null) {
        $failed[] = $country->name . ': ' . $landmark;
        continue;
    }
    $placed = false;
    foreach ($candidates as $candidate) {
        if ($accept($country, $landmark, $candidate)) {
            $placed = true;
            break;
        }
    }
    if (!$placed) {
        $unplaced[] = $country->name . ': ' . $landmark;
    }
}

// The catalogue's own order, so the generated file reads country by country
// rather than in the order the two passes happened to answer.
usort($places, fn(Place $a, Place $b) => [$a->code, $a->landmark] <=> [$b->code, $b->landmark]);

// ------------------------------------------------------------------
// Write it out
// ------------------------------------------------------------------

$rows = '';
foreach ($places as $p) {
    $rows .= sprintf(
        "            ['%s', %s, %s, %s, %s],\n",
        $p->code,
        var_export($p->landmark, true),
        var_export($p->lat, true),
        var_export($p->lon, true),
        var_export($p->image, true),
    );
}

$file = <<<PHP
<?php

namespace Samples\FlagQuiz;

/**
 * Where each landmark is, and a picture of it — so the map can point at the
 * Eiffel Tower rather than at France.
 *
 * Generated by tools/generate-landmarks.php from Wikipedia: its search picks
 * the article for a landmark named alongside its country, and the article
 * carries both the coordinates and the lead image. Every coordinate was
 * checked to fall inside that country's Natural Earth borders before being
 * written here, so a search that wandered off to a same-named place on another
 * continent left no entry at all — and a landmark with no entry falls back to
 * the map highlighting its country, which is what the map did before any of
 * this. Hand-editing is pointless; regenerate when the landmark catalogue
 * changes.
 *
 * A few entries carry no picture: the article had no lead image. Those pin and
 * name the landmark without one.
 */
final class LandmarkPlaces
{
    /**
     * Where a landmark is. Keyed by country and landmark name, because the
     * same place can belong to two countries — Victoria Falls is Zambia's and
     * Zimbabwe's both, and each keeps its own entry.
     */
    public static function for(string \$code, string \$landmark): ?LandmarkPlace
    {
        return self::map()[self::key(\$code, \$landmark)] ?? null;
    }

    private static function key(string \$code, string \$landmark): string
    {
        return strtolower(\$code) . '|' . Country::normalize(\$landmark);
    }

    /** @return array<string, LandmarkPlace> */
    private static function map(): array
    {
        static \$cache = null;
        if (\$cache !== null) {
            return \$cache;
        }

        // [country, landmark, latitude, longitude, picture]
        \$rows = [
{$rows}        ];

        \$cache = [];
        foreach (\$rows as [\$code, \$landmark, \$lat, \$lon, \$image]) {
            \$cache[self::key(\$code, \$landmark)] = new LandmarkPlace(\$landmark, \$lat, \$lon, \$image);
        }
        return \$cache;
    }
}

PHP;

if (!$dryRun) {
    file_put_contents(__DIR__ . '/../src/LandmarkPlaces.php', $file);
} else {
    echo "\n(dry run over " . implode(', ', $only) . " — nothing written)\n";
}

printf(
    "\n%d landmarks: %d placed, %d unplaced, %d lookups failed, %d placed without a picture\n",
    $total, count($places), count($unplaced), count($failed), count($noImage),
);
if ($failed !== []) {
    echo "\nlookup failed (re-run for these):\n  " . implode("\n  ", $failed) . "\n";
}
if ($unplaced !== []) {
    echo "\nunplaced:\n  " . implode("\n  ", $unplaced) . "\n";
}
if ($noImage !== []) {
    echo "\nno picture:\n  " . implode("\n  ", $noImage) . "\n";
}
