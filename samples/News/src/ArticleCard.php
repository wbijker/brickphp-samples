<?php

namespace Samples\News;

use BrickPHP\UI\Color;
use BrickPHP\UI\Direction;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\Router;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\StatelessComponent;
use BrickPHP\VNode\VNode;

/**
 * One row in the LATEST NEWS list. Pure function of the Article it
 * receives — no state, no lifecycle. Stacks vertically on small
 * screens and switches to image-left at md+.
 */
class ArticleCard extends StatelessComponent
{
    public function __construct(private Article $article) {}


    protected function build(): VNode
    {
        $a = $this->article;

        return UI::row()
            ->direction(Direction::column())
            ->direction(Direction::row(), Pseudo::md())
            ->background(Color::white())
            ->rounded(Unit::rem(0.375))
            ->overflow()
            ->shadow(Shadow::Small)
            ->shadow(Shadow::Medium, Pseudo::hover())
            ->animated()
            ->clickable()
            ->alignTop()
            ->onClick(fn() => Router::navigate(new ArticleRoute($a->slug())))
            ->content(
                UI::image($a->coverImage, $a->title)
                    ->width(Unit::full())
                    ->width(Unit::px(220), Pseudo::md())
                    ->height(Unit::px(180))
                    ->height(Unit::px(160), Pseudo::md())
                    ->noShrink(Pseudo::md())
                    ->objectCover(),
                UI::column()
                    ->grow()
                    ->padding(Unit::rem(1))
                    ->padding(Unit::rem(1.25), Pseudo::md())
                    ->gap(Unit::rem(0.5))
                    ->content(
                        UI::text(strtoupper($a->category))
                            ->fontSize(FontSize::ExtraSmall)
                            ->weight(FontWeight::Bold)
                            ->color(News::categoryColor($a->category)),
                        UI::text($a->title)
                            ->fontSize(FontSize::Base)
                            ->fontSize(FontSize::Large, Pseudo::md())
                            ->weight(FontWeight::SemiBold)
                            ->color(Color::gray(900))
                            ->color(Color::red(600), Pseudo::hover()),
                        UI::text($a->excerpt)
                            ->fontSize(FontSize::Small)
                            ->color(Color::gray(600)),
                        UI::text($a->formattedDate())
                            ->fontSize(FontSize::ExtraSmall)
                            ->color(Color::gray(500)),
                    )
            );
    }
}
