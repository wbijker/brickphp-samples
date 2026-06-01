<?php

namespace Samples\News;

use BrickPHP\State\SessionStateManager;
use BrickPHP\State\StateManager;
use BrickPHP\UI\Router;
use BrickPHP\VNode\App;
use BrickPHP\VNode\VNode;

class NewsApp extends App
{
    public function title(): string
    {
        return 'News';
    }

    public function state(): StateManager
    {
        return new SessionStateManager();
    }

    protected function registerAssets(App $app): void
    {
        Router::registerAssets($app);
        Leaflet::registerAssets($app);
    }

    protected function view(): VNode
    {
        return new News();
    }



}
