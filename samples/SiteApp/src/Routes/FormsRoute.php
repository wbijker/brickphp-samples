<?php

namespace Samples\SiteApp\Routes;

use BrickPHP\UI\BaseRoute;

class FormsRoute extends BaseRoute
{
    public static function handle(string $uri): ?static
    {
        return $uri === '/forms' ? new static() : null;
    }

    public function toUrl(): string
    {
        return '/forms';
    }
}
