<?php

namespace Samples\Docs\Routes;

use BrickPHP\UI\BaseRoute;

class HomeRoute extends BaseRoute
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
