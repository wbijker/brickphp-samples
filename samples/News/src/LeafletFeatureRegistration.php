<?php

namespace Samples\News;

use BrickPHP\Events\EventRegistration;

/**
 * Name-carrier registration for GeoJSON feature clicks. The client-side
 * listeners are wired per feature inside the BrickLeaflet runtime (see
 * {@see Leaflet::runtimeJs()}), not through the diff — so add()/remove() are
 * no-ops. This registration only carries the event name so
 * {@see \BrickPHP\UI\TagDomNode::on()} stores the server handler, which the
 * server resolves when an {@see Leaflet::EVENT_FEATURE} dispatch arrives.
 */
final class LeafletFeatureRegistration implements EventRegistration
{
    public function __construct(private string $event) {}

    public function eventName(): string
    {
        return $this->event;
    }

    public function add(array $path): void {}

    public function remove(array $path): void {}
}
