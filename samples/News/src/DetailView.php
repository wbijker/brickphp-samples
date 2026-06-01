<?php

namespace Samples\News;

use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\Router;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\StatelessComponent;
use BrickPHP\VNode\VNode;

/**
 * Single-article page rendered at /article/<slug> — back link, hero
 * image, category, title, date, excerpt, body copy.
 */
class DetailView extends StatelessComponent
{
    public function __construct(private Article $article) {}

    protected function build(): VNode
    {
        $a = $this->article;

        return UI::column()
            ->maxWidth(Unit::px(900))
            ->width(Unit::full())
            ->marginX(Unit::auto())
            ->padding(x: Unit::rem(1),   y: Unit::rem(1.25))
            ->padding(x: Unit::rem(1.5), y: Unit::rem(2), pseudo: Pseudo::md())
            ->gap(Unit::rem(1.5))
            ->content(
                $this->backLink(),
                UI::column()
                    ->background(Color::white())
                    ->rounded(Unit::rem(0.5))
                    ->overflow()
                    ->shadow(Shadow::Small)
                    ->content(
                        UI::image($a->coverImage, $a->title)
                            ->width(Unit::full())
                            ->height(Unit::px(240))
                            ->height(Unit::px(360), Pseudo::md())
                            ->height(Unit::px(440), Pseudo::lg())
                            ->objectCover(),
                        UI::column()
                            ->padding(Unit::rem(1.25))
                            ->padding(Unit::rem(2), Pseudo::md())
                            ->gap(Unit::rem(1))
                            ->content(
                                News::categoryLabel($a->category),
                                UI::text($a->title)
                                    ->fontSize(FontSize::TwoXL)
                                    ->fontSize(FontSize::ThreeXL, Pseudo::md())
                                    ->fontSize(FontSize::FourXL, Pseudo::lg())
                                    ->weight(FontWeight::Bold)
                                    ->color(Color::gray(900)),
                                UI::text($a->formattedDate())
                                    ->fontSize(FontSize::Small)
                                    ->color(Color::gray(500)),
                                UI::text($a->excerpt)
                                    ->fontSize(FontSize::Base)
                                    ->fontSize(FontSize::Large, Pseudo::md())
                                    ->weight(FontWeight::SemiBold)
                                    ->color(Color::gray(700))
                                    ->paddingTop(Unit::rem(0.5)),
                                UI::text($a->content)
                                    ->fontSize(FontSize::Base)
                                    ->color(Color::gray(700)),
                            )
                    )
            );
    }

    private function backLink(): UIElement
    {
        return UI::text('← Back to all news')
            ->fontSize(FontSize::Small)
            ->weight(FontWeight::SemiBold)
            ->color(Color::red(600))
            ->color(Color::red(700), Pseudo::hover())
            ->clickable()
            ->onClick(fn() => Router::navigate(new NewsListRoute()));
    }
}
