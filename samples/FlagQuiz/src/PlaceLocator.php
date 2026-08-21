<?php

namespace Samples\FlagQuiz;

/**
 * Whether a click on the map landed on the place it was meant to.
 *
 * Asked where the Nile is, the answer is a point on the map rather than a
 * country, so it is judged against the river's own line: near enough to the
 * Zambezi is right whether the click fell in Zambia, Zimbabwe or the water in
 * between. That is the whole reason a place question exists — the thing is not
 * its country's, and neither is the answer.
 *
 * "Near enough" is generous and has to be. A river drawn at the width of a
 * hair at continent zoom cannot be clicked to the pixel, and a landmark is one
 * point on a map showing a million square kilometres. The tolerances below are
 * the size of the thing being pointed at rather than the precision of the
 * pointing: a landmark is a spot and gets a spot's worth of room, a range
 * covers ground and is judged on whether the click is on it.
 */
final class PlaceLocator
{
    /**
     * How far from the place a click may land and still count, in kilometres.
     *
     * A landmark is a single point and needs the most room — it is a dot on a
     * continent. A river is a long line, so being anywhere along it already
     * takes some finding and the allowance can be tighter. Ranges and lakes
     * cover ground and are mostly judged by falling inside them, with the
     * allowance only catching the edges.
     */
    private const TOLERANCE_KM = [
        'landmark' => 150.0,
        'river' => 90.0,
        'mountains' => 90.0,
        'lake' => 70.0,
    ];

    /**
     * Whether the click answers this country's place question — near any one
     * of the places it carries for that attribute. Any of them, because the
     * question named them all: shown "Rhine / Rhône / Aare / Inn", finding any
     * of the four is finding what was asked for.
     */
    public static function hits(Attribute $place, Country $country, float $lat, float $lon): bool
    {
        $tolerance = self::TOLERANCE_KM[$place->value] ?? 100.0;

        foreach ($place->valuesOf($country) as $name) {
            if ($place === Attribute::Landmark) {
                $at = LandmarkPlaces::for($country->code, $name);
                if ($at !== null && self::km($lat, $lon, $at->lat, $at->lon) <= $tolerance) {
                    return true;
                }
                continue;
            }

            foreach (PlaceShapes::for($place, $country->code, $name) as $path) {
                // Areas are answered by clicking inside them, however far that
                // is from an edge; the tolerance below then catches the rim.
                if ($place !== Attribute::River && self::inside($lat, $lon, $path)) {
                    return true;
                }
                if (self::nearPath($lat, $lon, $path) <= $tolerance) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * The closest the click comes to the line, in kilometres — measured to the
     * segments rather than to the corners, since a river drawn between two
     * points a hundred kilometres apart is the line and not its ends.
     *
     * @param array<int, array{0: float, 1: float}> $path
     */
    private static function nearPath(float $lat, float $lon, array $path): float
    {
        $best = INF;
        for ($i = 1, $n = count($path); $i < $n; $i++) {
            $d = self::nearSegment($lat, $lon, $path[$i - 1], $path[$i]);
            if ($d < $best) {
                $best = $d;
            }
        }
        // A one-point path has no segment; fall back to the point itself.
        if ($best === INF && $path !== []) {
            $best = self::km($lat, $lon, $path[0][0], $path[0][1]);
        }
        return $best;
    }

    /**
     * Distance to a segment. Worked in flat degrees with longitude squeezed by
     * the latitude — over a segment a few hundred kilometres long the earth's
     * curve is far below the tolerances here, and this keeps it arithmetic.
     *
     * @param array{0: float, 1: float} $a
     * @param array{0: float, 1: float} $b
     */
    private static function nearSegment(float $lat, float $lon, array $a, array $b): float
    {
        $squeeze = cos(deg2rad($lat));
        $px = ($lon - $a[1]) * $squeeze;
        $py = $lat - $a[0];
        $bx = ($b[1] - $a[1]) * $squeeze;
        $by = $b[0] - $a[0];

        $len = $bx * $bx + $by * $by;
        // How far along the segment the closest point sits, clamped to its ends.
        $t = $len > 1e-12 ? max(0.0, min(1.0, ($px * $bx + $py * $by) / $len)) : 0.0;

        return self::km($lat, $lon, $a[0] + $by * $t, $a[1] + ($b[1] - $a[1]) * $t);
    }

    /**
     * Ray casting: whether the point is inside the ring. Coordinates are
     * [latitude, longitude], the order the shapes are stored in.
     *
     * @param array<int, array{0: float, 1: float}> $ring
     */
    private static function inside(float $lat, float $lon, array $ring): bool
    {
        $in = false;
        $n = count($ring);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$yi, $xi] = $ring[$i];
            [$yj, $xj] = $ring[$j];
            if (($yi > $lat) !== ($yj > $lat)
                && $lon < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi) {
                $in = !$in;
            }
        }
        return $in;
    }

    private static function km(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return 6371.0 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
