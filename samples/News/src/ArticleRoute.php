<?php

namespace Samples\News;

use BrickPHP\UI\BaseRoute;

/**
 * Matches `/article/<slug>` and carries the slug to the route handler. The
 * handler is responsible for resolving the slug to an Article (e.g. via
 * NewsData::findBySlug) and rendering the detail view.
 */
class ArticleRoute extends BaseRoute
{
    public function __construct(
        public string $slug,
    ) {}

    public static function handle(string $uri): ?static
    {
        // Tolerant of an optional trailing slash, uppercase letters, and
        // percent-encoded chars — anything that round-trips to the canonical
        // lowercase slug. The previous strict pattern rejected a stray '/'
        // and silently routed to `body()` (the main page), producing the
        // confusing "URL changes, content doesn't" symptom.
        if (preg_match('#^/article/([^/]+)/?$#', $uri, $m)) {
            return new static(strtolower(urldecode($m[1])));
        }
        return null;
    }

    public function toUrl(): string
    {
        return '/article/' . $this->slug;
    }
}
