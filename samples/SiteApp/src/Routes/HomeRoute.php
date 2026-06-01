<?php

namespace Samples\SiteApp\Routes;

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
