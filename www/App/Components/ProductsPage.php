<?php

namespace App\Components;

use BrickPHP\Html\A;
use BrickPHP\Html\Div;
use BrickPHP\Nodes\Component;
use BrickPHP\Nodes\HtmlText;
use BrickPHP\Nodes\Node;
use BrickPHP\Route\RouteLink;

class ProductsPage extends Component
{
    public function __construct(private ProductRoute $product)
    {
    }

    function shouldPatch(self|Component $other): bool
    {
        return $this->product->category != $other->product->category;
    }


    function render(): Node
    {
        return new Div(children: [
            new Div(children: [new HtmlText("Category: " . $this->product->category)]),
            new Div(children: [new HtmlText("Product: " . $this->product->product)]),
            new Div(children: [new HtmlText("Limit: " . $this->product->limit)]),

            new A(class: "underline mx-2 cursor-pointer", href: "/", children: [new HtmlText("Home")]),


            new RouteLink(
                href: Routes::$product->toUrl(new ProductRoute(category: "Clothes", product: 12, limit: 10)),
                text: "Clothes"
            ),

            new RouteLink(
                href: Routes::$product->toUrl(new ProductRoute(category: "Electronics", product: 44, limit: 11)),
                text: "Electronics"
            ),

            new RouteLink(
                href: Routes::$product->toUrl(new ProductRoute(category: "Appliances", product: 52, limit: 12)),
                text: "Appliances"
            )
        ]);
    }
}