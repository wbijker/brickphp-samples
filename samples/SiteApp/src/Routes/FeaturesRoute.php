<?php

namespace Samples\SiteApp\Routes;

use BrickPHP\UI\BaseRoute;

class FeaturesRoute extends BaseRoute
{
    public static function handle(string $uri): ?static
    {
        return $uri === '/features' ? new static() : null;
    }

    public function toUrl(): string
    {
        return '/features';
    }
}
