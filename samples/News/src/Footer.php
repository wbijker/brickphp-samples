<?php

namespace Samples\News;

use BrickPHP\UI\Color;
use BrickPHP\UI\Direction;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\StatelessComponent;
use BrickPHP\VNode\VNode;

/**
 * Site footer — copyright line on the left, nav-style links on the
 * right. Stacks on small screens, splits across the row on md+.
 */
class Footer extends StatelessComponent
{
    protected function build(): VNode
    {
        return UI::container()
            ->background(Color::gray(900))
            ->padding(x: Unit::rem(1),   y: Unit::rem(2))
            ->padding(x: Unit::rem(1.5), y: Unit::rem(2.5), pseudo: Pseudo::md())
            ->content(
                UI::row()
                    ->direction(Direction::column())
                    ->direction(Direction::row(), Pseudo::md())
                    ->maxWidth(Unit::px(1200))
                    ->width(Unit::full())
                    ->marginX(Unit::auto())
                    ->alignBetween()
                    ->alignMiddle()
                    ->gap(Unit::rem(1))
                    ->content(
                        UI::text('© 2026. All rights reserved.')
                            ->fontSize(FontSize::Small)
                            ->color(Color::gray(400)),
                        UI::row()
                            ->wrap()
                            ->alignCenter()
                            ->gap(Unit::rem(1))
                            ->gap(Unit::rem(1.5), Pseudo::md())
                            ->content(
                                $this->link('About'),
                                $this->link('Advertise'),
                                $this->link('Contact'),
                                $this->link('Privacy'),
                            )
                    )
            );
    }

    private function link(string $label): UIElement
    {
        return UI::text($label)
            ->fontSize(FontSize::Small)
            ->color(Color::gray(400))
            ->color(Color::white(), Pseudo::hover())
            ->clickable();
    }
}
