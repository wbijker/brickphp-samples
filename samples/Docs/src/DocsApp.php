<?php

namespace Samples\Docs;

use BrickPHP\State\SessionStateManager;
use BrickPHP\State\StateManager;
use BrickPHP\UI\Color;
use BrickPHP\UI\Router;
use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\App;
use BrickPHP\VNode\VNode;
use Samples\Docs\Components\DocsFooter;
use Samples\Docs\Components\DocsHeader;
use Samples\Docs\Data\ElementCatalog;
use Samples\Docs\Pages\ApiIndexPage;
use Samples\Docs\Pages\ElementDocPage;
use Samples\Docs\Pages\LandingPage;
use Samples\Docs\Routes\ApiIndexRoute;
use Samples\Docs\Routes\ElementRoute;
use Samples\Docs\Routes\HomeRoute;

class DocsApp extends App
{
    public function title(): string
    {
        return 'BrickPHP — Server-Powered Web Applications in PHP';
    }

    public function state(): StateManager
    {
        return new SessionStateManager();
    }

    protected function registerAssets(App $app): void
    {
        Router::registerAssets($app);
    }

    protected function view(): VNode
    {
        return UI::column()
            ->minHeight(Unit::vh(100))
            ->width(Unit::full())
            ->background(Color::slate(50))
            ->content(
                new DocsHeader(),
                UI::column()
                    ->grow()
                    ->width(Unit::full())
                    ->content(
                        Router::router()
                            ->register(HomeRoute::class,     fn() => new LandingPage())
                            ->register(ApiIndexRoute::class, fn() => new ApiIndexPage())
                            ->register(ElementRoute::class,  function (ElementRoute $r) {
                                $entry = ElementCatalog::find($r->slug);
                                return $entry !== null
                                    ? new ElementDocPage($entry)
                                    : new ApiIndexPage($r->slug);
                            })
                            ->fallback(new LandingPage()),
                    ),
                new DocsFooter(),
            );
    }
}
