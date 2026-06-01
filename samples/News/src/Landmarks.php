<?php

namespace Samples\News;

/**
 * Static catalog of 20 well-known world landmarks. Used by the News
 * front page to drive the Leaflet map — clicking a name pans the map
 * to that landmark's coordinates.
 */
class Landmarks
{
    /** @return array<array{name: string, coords: array{0: float, 1: float}}> */
    public static function all(): array
    {
        return [
            ['name' => 'Eiffel Tower',         'coords' => [48.8584,   2.2945]],
            ['name' => 'Statue of Liberty',    'coords' => [40.6892, -74.0445]],
            ['name' => 'Great Wall of China',  'coords' => [40.4319, 116.5704]],
            ['name' => 'Sydney Opera House',   'coords' => [-33.8568, 151.2153]],
            ['name' => 'Taj Mahal',            'coords' => [27.1751,  78.0421]],
            ['name' => 'Colosseum',            'coords' => [41.8902,  12.4922]],
            ['name' => 'Machu Picchu',         'coords' => [-13.1631, -72.5450]],
            ['name' => 'Christ the Redeemer',  'coords' => [-22.9519, -43.2105]],
            ['name' => 'Pyramids of Giza',     'coords' => [29.9792,  31.1342]],
            ['name' => 'Big Ben',              'coords' => [51.5007,  -0.1246]],
            ['name' => 'Mount Fuji',           'coords' => [35.3606, 138.7274]],
            ['name' => 'Burj Khalifa',         'coords' => [25.1972,  55.2744]],
            ['name' => 'Petra',                'coords' => [30.3285,  35.4444]],
            ['name' => 'Stonehenge',           'coords' => [51.1789,  -1.8262]],
            ['name' => 'Angkor Wat',           'coords' => [13.4125, 103.8670]],
            ['name' => 'Acropolis',            'coords' => [37.9715,  23.7257]],
            ['name' => 'Niagara Falls',        'coords' => [43.0962, -79.0377]],
            ['name' => 'Mount Everest',        'coords' => [27.9881,  86.9250]],
            ['name' => 'Santorini',            'coords' => [36.3932,  25.4615]],
            ['name' => 'Table Mountain',       'coords' => [-33.9628,  18.4098]],
        ];
    }
}
