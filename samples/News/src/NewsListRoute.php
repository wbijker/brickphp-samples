<?php

namespace Samples\News;

use BrickPHP\UI\BaseRoute;

/**
 * Matches the news index — `/`.
 */
class NewsListRoute extends BaseRoute
{
    public static function handle(string $uri): ?static
    {
        return $uri === '/' ? new static() : null;
    }

    public function toUrl(): string
    {
        return '/';
    }
}
