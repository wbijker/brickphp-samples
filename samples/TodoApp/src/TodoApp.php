<?php

namespace Samples\TodoApp;

use BrickPHP\State\SessionStateManager;
use BrickPHP\State\StateManager;
use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\App;
use BrickPHP\VNode\VNode;

class TodoApp extends App
{
    public function title(): string
    {
        return 'TodoMVC - Brick';
    }

    public function state(): StateManager
    {
        return new SessionStateManager();
    }

    protected function loader(): ?VNode
    {
        return UI::row()
            ->fixed()
            ->inset(Unit::px(0))
            ->layer(9999)
            ->alignCenter()
            ->alignMiddle()
            ->background(Color::black()->alpha(0.25))
            ->content(
                UI::text('Loading…')
                    ->background(Color::white())
                    ->padding(Unit::rem(0.75))
                    ->paddingX(Unit::rem(1.25))
                    ->rounded(Unit::rem(0.5))
                    ->color(Color::gray(700))
                    ->fontSize(FontSize::Small)
            );
    }

    protected function view(): VNode
    {
        return new TodoList();
    }
}
