<?php

namespace Samples\SiteApp\Pages;

use Samples\SiteApp\Components\Navbar;
use Samples\SiteApp\Routes\ComponentsRoute;
use Samples\SiteApp\Routes\FeaturesRoute;
use Samples\SiteApp\Routes\FormsRoute;
use Samples\SiteApp\Routes\HomeRoute;
use Samples\SiteApp\Routes\StateRoute;
use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\Router;
use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;

class SiteApp extends Component
{
    protected function build(): VNode
    {
        return UI::column()
            ->background(Color::slate(50))
            ->minHeight(Unit::screen())
            ->content(
                new Navbar(),
                Router::router()
                    ->register(HomeRoute::class,       fn() => new HomePage())
                    ->register(FeaturesRoute::class,   fn() => new FeaturesPage())
                    ->register(ComponentsRoute::class, fn() => new ComponentsPage())
                    ->register(FormsRoute::class,      fn() => new FormsPage())
                    ->register(StateRoute::class,      fn() => new StatePage())
                    ->fallback(new HomePage()),
                $this->buildFooter(),
            );
    }

    private function buildFooter(): VNode
    {
        return UI::footer()
            ->background(Color::slate(900))
            ->padding(Unit::extraLarge())
            ->content(
                UI::column()
                    ->maxWidth(Unit::px(900))
                    ->marginX(Unit::auto())
                    ->gap(Unit::small())
                    ->alignCenter()
                    ->content(
                        UI::text('Brick — Server-Powered Web Applications')
                            ->color(Color::slate(400))
                            ->fontSize(FontSize::Small),
                        UI::text('Pure PHP. No Node. No external libraries.')
                            ->color(Color::slate(500))
                            ->fontSize(FontSize::ExtraSmall),
                    )
            );
    }
}
