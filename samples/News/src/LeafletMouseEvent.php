<?php

namespace Samples\News;

/**
 * Server-side counterpart of Leaflet's `MouseEvent`. The client
 * forwards `event.latlng` (a `L.LatLng`) verbatim, which serializes
 * over the wire as `{lat, lng}` — so `::from()` receives that flat
 * shape and wraps it back into a `LeafletLatLng` inside the event
 * object. Other Leaflet event fields (layerPoint, containerPoint,
 * originalEvent) are intentionally left out until a use case needs
 * them.
 */
class LeafletMouseEvent
{
    public function __construct(
        public readonly LeafletLatLng $latlng,
    ) {}

    /** @param array{lat: float, lng: float} $raw */
    public static function from(array $raw): self
    {
        return new self(LeafletLatLng::from($raw));
    }
}
