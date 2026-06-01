<?php

namespace Samples\SiteApp\Routes;

use BrickPHP\UI\BaseRoute;

class StateRoute extends BaseRoute
{
    public static function handle(string $uri): ?static
    {
        return $uri === '/state' ? new static() : null;
    }

    public function toUrl(): string
    {
        return '/state';
    }
}
