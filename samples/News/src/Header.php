<?php

namespace Samples\News;

use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\StatelessComponent;
use BrickPHP\VNode\VNode;

/**
 * Top site header — "My News" wordmark on the left and a wrapping
 * row of section nav links on the right. Stateless — no props; the
 * active nav item is hard-coded.
 */
class Header extends StatelessComponent
{
    protected function build(): VNode
    {
        return UI::container()
            ->background(Color::white())
            ->shadow(Shadow::Small)
            ->content(
                UI::row()
                    ->wrap()
                    ->alignBetween()
                    ->alignMiddle()
                    ->gap(Unit::rem(0.75))
                    ->maxWidth(Unit::px(1200))
                    ->width(Unit::full())
                    ->marginX(Unit::auto())
                    ->padding(x: Unit::rem(1),   y: Unit::rem(0.75))
                    ->padding(x: Unit::rem(1.5), y: Unit::rem(1), pseudo: Pseudo::md())
                    ->content(
                        UI::row()
                            ->alignMiddle()
                            ->gap(Unit::rem(0.5))
                            ->content(
                                UI::text('My')
                                    ->fontSize(FontSize::ExtraLarge)
                                    ->fontSize(FontSize::TwoXL, Pseudo::md())
                                    ->weight(FontWeight::Bold)
                                    ->color(Color::gray(900)),
                                UI::text('News')
                                    ->fontSize(FontSize::ExtraLarge)
                                    ->fontSize(FontSize::TwoXL, Pseudo::md())
                                    ->weight(FontWeight::Bold)
                                    ->color(Color::red(600))
                            ),
                        UI::row()
                            ->wrap()
                            ->gap(Unit::rem(0.75))
                            ->gap(Unit::rem(1.25), Pseudo::md())
                            ->gap(Unit::rem(1.5), Pseudo::lg())
                            ->content(
                                $this->navLink('News', true),
                                $this->navLink('Industry'),
                                $this->navLink('Mobile'),
                                $this->navLink('Internet'),
                                $this->navLink('Business'),
                                $this->navLink('Banking'),
                                $this->navLink('Reviews'),
                            )
                    )
            );
    }

    private function navLink(string $label, bool $active = false): UIElement
    {
        $base = UI::text($label)
            ->fontSize(FontSize::Small)
            ->weight(FontWeight::SemiBold)
            ->clickable()
            ->paddingY(Unit::rem(0.25))
            ->color(Color::red(600), Pseudo::hover());

        if ($active) {
            return $base
                ->color(Color::red(600))
                ->borderBottom(2)
                ->borderColor(Color::red(600));
        }

        return $base->color(Color::gray(700));
    }
}
