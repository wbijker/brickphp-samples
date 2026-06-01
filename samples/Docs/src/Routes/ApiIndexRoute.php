<?php

namespace Samples\Docs\Routes;

use BrickPHP\UI\BaseRoute;

class ApiIndexRoute extends BaseRoute
{
    public static function handle(string $uri): ?static
    {
        return $uri === '/api' || $uri === '/api/' ? new static() : null;
    }

    public function toUrl(): string
    {
        return '/api';
    }
}
