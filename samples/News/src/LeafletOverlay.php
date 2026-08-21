<?php

namespace Samples\News;

/**
 * Something drawn on the map over the base layers, pushed fresh on every
 * render — a line, an area, or a pin.
 *
 * The distinction from everything else this component draws is *when*. The
 * GeoJSON layer is fetched and built once and then restyled in place; the
 * `addMarker` / `addPolygon` family is staged into the map's first render and
 * never runs again. Neither can follow state that changes between renders: a
 * river that is the Zambezi now and the Nile after the next click, a set of
 * pins that belongs to whichever country is being looked at.
 *
 * An overlay can. {@see Leaflet::setOverlays()} takes the whole set each
 * render and the runtime swaps the previous set out for it, so what is on the
 * map is always what the last render asked for and nothing accumulates.
 *
 * Subclassed rather than carrying a "kind" string, so what is being drawn is a
 * type and the shape of its data comes with it.
 */
abstract class LeafletOverlay
{
    /**
     * The overlay as the runtime wants it. The `kind` in the returned array is
     * the wire format, not the domain — PHP passes types, the browser gets a
     * tag it can switch on.
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;
}
