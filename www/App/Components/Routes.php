<?php

namespace App\Components;

use BrickPHP\Route\RoutePath;

class Routes
{
    public static string $about = "/about";
    public static RoutePath $product;

    public static function init(): void
    {
        self::$product = new RoutePath("/products/{category}/{product}", ["limit"], ProductRoute::class);
    }
}

Routes::init();