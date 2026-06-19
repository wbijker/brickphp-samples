<?php

namespace Samples\FlagQuiz\Components;

use BrickPHP\Events\EventRegistration;

/**
 * Binds the server handler for the map's `country:pick` event. The client-side
 * listener is wired per country polygon inside `fqInitMap` (see FlagQuizApp),
 * not through the diff — so add()/remove() are no-ops. This registration only
 * carries the event name so {@see \BrickPHP\UI\TagDomNode::on()} stores the
 * callback for the server to resolve when a dispatch arrives.
 */
final class MapPickRegistration implements EventRegistration
{
    public function eventName(): string
    {
        return 'country:pick';
    }

    public function add(array $path): void {}

    public function remove(array $path): void {}
}
