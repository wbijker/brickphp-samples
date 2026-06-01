<?php

namespace Samples\SiteApp\Components;

use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\Router;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;

class Navbar extends Component
{
    protected function build(): VNode
    {
        return UI::nav()
            ->background(Color::white())
            ->shadow(Shadow::Small)
            ->paddingX(Unit::extraLarge())
            ->paddingY(Unit::medium())
            ->content(
                UI::row()
                    ->maxWidth(Unit::px(900))
                    ->marginX(Unit::auto())
                    ->alignMiddle()
                    ->alignBetween()
                    ->content(
                        UI::text('Brick')
                            ->fontSize(FontSize::ExtraLarge)
                            ->weight(FontWeight::Bold)
                            ->color(Color::indigo(600))
                            ->clickable()
                            ->onClick(fn() => Router::navigate('/')),

                        UI::row()
                            ->gap(Unit::small())
                            ->content(
                                $this->navItem('Home', '/'),
                                $this->navItem('Features', '/features'),
                                $this->navItem('Components', '/components'),
                                $this->navItem('Forms', '/forms'),
                                $this->navItem('State', '/state'),
                            )
                    )
            );
    }

    private function navItem(string $label, string $path): VNode
    {
        return UI::button($label)
            ->borderNone()
            ->background(Color::transparent())
            ->color(Color::slate(600))
            ->color(Color::indigo(700), Pseudo::hover())
            ->padding(Unit::small())
            ->paddingX(Unit::medium())
            ->rounded(Unit::roundedLg())
            ->fontSize(FontSize::Small)
            ->clickable()
            ->onClick(fn() => Router::navigate($path));
    }
}
