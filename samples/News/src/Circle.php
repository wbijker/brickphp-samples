<?php

namespace Samples\News;

/**
 * Style + radius for a Leaflet L.circle. Maps 1:1 onto the option
 * object that L.circle() accepts as its second argument.
 */
class Circle
{
    public function __construct(
        public string $color       = 'red',
        public string $fillColor   = '#f03',
        public float  $fillOpacity = 0.5,
        public int    $radius      = 500,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'color'       => $this->color,
            'fillColor'   => $this->fillColor,
            'fillOpacity' => $this->fillOpacity,
            'radius'      => $this->radius,
        ];
    }
}
