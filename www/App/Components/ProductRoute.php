<?php

namespace App\Components;


class ProductRoute
{
    public function __construct(
        public string $category = "",
        public int $product = 0,
        public ?int $limit = null)
    {

    }

}