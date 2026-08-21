<?php

namespace Samples\FlagQuiz;

/**
 * The six inhabited continents. Each {@see Country} belongs to one, and the
 * start screen lets the player restrict a game to a chosen subset. Persisted as
 * session state (string-backed, so it round-trips through SessionStateManager).
 */
enum Continent: string
{
    case Africa = 'africa';
    case Asia = 'asia';
    case Europe = 'europe';
    case NorthAmerica = 'north-america';
    case SouthAmerica = 'south-america';
    case Oceania = 'oceania';

    /** Human-readable name for the picker chip. */
    public function label(): string
    {
        return match ($this) {
            self::Africa => 'Africa',
            self::Asia => 'Asia',
            self::Europe => 'Europe',
            self::NorthAmerica => 'North America',
            self::SouthAmerica => 'South America',
            self::Oceania => 'Oceania',
        };
    }

    /**
     * Where a map should open to show this continent: the middle of its
     * countries, at a zoom that holds it.
     *
     * The centre is worked out from {@see CountryPoints} rather than written
     * down, so it follows the catalogue instead of drifting from it. The zoom
     * is not: how close to sit depends on how far a continent sprawls, and
     * Oceania scattered across a third of the Pacific wants a different
     * distance from Europe packed into a corner. Those are judgements, and
     * judgements are written down.
     */
    public function view(): MapView
    {
        $lats = [];
        // Longitude is a circle, so it is averaged as one — as a heading
        // rather than a number. Oceania is the reason: Fiji sits at 178° and
        // Kiribati at −157°, twenty-five degrees apart across the date line,
        // and averaged as plain numbers they come out at 10° — the Gulf of
        // Guinea, half a world from either. Summed as directions they average
        // to the Pacific, which is where Oceania is.
        $x = 0.0;
        $y = 0.0;
        foreach (Country::all() as $country) {
            if ($country->continent !== $this) {
                continue;
            }
            $point = CountryPoints::for($country->code);
            if ($point === null) {
                continue;
            }
            $lats[] = $point->latitude;
            $x += cos(deg2rad($point->longitude));
            $y += sin(deg2rad($point->longitude));
        }
        if ($lats === []) {
            return MapView::world();
        }

        return new MapView(
            new LatLon(
                round(array_sum($lats) / count($lats), 2),
                round(rad2deg(atan2($y, $x)), 2),
            ),
            $this->zoom(),
        );
    }

    /**
     * How close the map sits when it opens on this continent.
     *
     * Close enough to read what is drawn on it, which is the point of opening
     * on a continent at all: at the whole-world zoom a river is a hair and the
     * landmark pins pile into each other faster than they can be told apart.
     * Not close enough to hold the whole continent in every case — Asia at
     * this distance is most of Asia — because a view you can read a bit of
     * beats a view that fits everything and shows nothing.
     */
    private function zoom(): int
    {
        return match ($this) {
            // Small enough to fit whole, and dense enough to want the room.
            self::Europe => 5,
            self::SouthAmerica, self::NorthAmerica, self::Africa, self::Asia => 4,
            // Not a landmass so much as a scattering across a third of the
            // Pacific; closer than this and the islands fall off opposite edges.
            self::Oceania => 4,
        };
    }
}
