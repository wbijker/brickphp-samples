<?php

namespace Samples\Docs\Routes;

use BrickPHP\UI\BaseRoute;

/**
 * Matches /api/<element-slug> — slug is the kebab-case name of a UI element,
 * e.g. /api/column, /api/text, /api/svg-path.
 */
class ElementRoute extends BaseRoute
{
    public function __construct(public string $slug) {}

    public static function handle(string $uri): ?static
    {
        if (preg_match('#^/api/([a-z0-9-]+)/?$#', $uri, $m)) {
            return new static($m[1]);
        }
        return null;
    }

    public function toUrl(): string
    {
        return '/api/' . $this->slug;
    }
}
