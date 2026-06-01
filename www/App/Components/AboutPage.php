<?php

namespace App\Components;

use BrickPHP\Html\Div;
use BrickPHP\Nodes\Component;
use BrickPHP\Nodes\HtmlText;
use BrickPHP\Nodes\Node;
use BrickPHP\Route\RouteLink;


class AboutPage extends Component
{
    function render(): Node
    {
        return new Div(children: [
            new HtmlText("About page"),

            new RouteLink(
                href: Routes::$product->toUrl(new ProductRoute("electronics", 44, 50)),
                text: "Kettle"
            )
        ]);
    }
}