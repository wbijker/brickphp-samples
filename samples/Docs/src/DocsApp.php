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

        // Design system fonts — Geist for everything except code, JetBrains
        // Mono for code, Material Symbols for icons.
        $app->addStyle('https://cdn.jsdelivr.net/npm/geist@1.3.0/dist/fonts/geist.min.css');
        $app->addStyle('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&display=swap');
        $app->addStyle('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200');

        // Body defaults: Geist as primary face; preflight already sets the
        // monospace fallback chain for code blocks, but we override the body
        // here so prose inherits Geist everywhere.
        $app->addStyleInline(<<<'CSS'
            body, button, input, select, textarea {
                font-family: 'Geist', ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
                font-feature-settings: 'cv11', 'ss01';
                -webkit-font-smoothing: antialiased;
            }
            code, pre, kbd, samp {
                font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
            }
            .material-symbols-outlined {
                font-family: 'Material Symbols Outlined';
                font-weight: normal;
                font-style: normal;
                font-size: 24px;
                line-height: 1;
                letter-spacing: normal;
                text-transform: none;
                display: inline-block;
                white-space: nowrap;
                word-wrap: normal;
                direction: ltr;
                -webkit-font-feature-settings: 'liga';
                -webkit-font-smoothing: antialiased;
                font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            }
            CSS);
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
