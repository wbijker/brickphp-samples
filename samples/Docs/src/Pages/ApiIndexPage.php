<?php

namespace Samples\Docs\Pages;

use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\Router;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use Samples\Docs\Components\DocsSidebar;
use Samples\Docs\Data\ElementCatalog;
use Samples\Docs\Data\ElementDoc;
use Samples\Docs\Routes\ElementRoute;

/**
 * Landing page for the /api section — sidebar on the left, category cards
 * on the right. If the user hits an unknown element slug, show a "not found"
 * message above the index.
 */
class ApiIndexPage extends Component
{
    public function __construct(private ?string $unknownSlug = null) {}

    protected function build(): VNode
    {
        return UI::row()
            ->maxWidth(Unit::px(1400))
            ->marginX(Unit::auto())
            ->width(Unit::full())
            ->alignTop()
            ->content(
                UI::column()
                    ->hidden()
                    ->flex(Pseudo::md())
                    ->content(new DocsSidebar()),
                UI::column()
                    ->grow()
                    ->padding(Unit::large())
                    ->gap(Unit::large())
                    ->content(
                        $this->unknownSlug !== null
                            ? $this->notFoundBanner($this->unknownSlug)
                            : UI::column()->content(),
                        $this->intro(),
                        $this->categoryGrid(),
                    ),
            );
    }

    private function notFoundBanner(string $slug): UIElement
    {
        return UI::row()
            ->background(Color::amber(50))
            ->bordered()
            ->borderColor(Color::amber(200))
            ->paddingX(Unit::medium())
            ->paddingY(Unit::small())
            ->rounded(Unit::roundedLg())
            ->content(
                UI::text("No element matches \"{$slug}\". Browse the catalog below.")
                    ->color(Color::amber(800))
                    ->fontSize(FontSize::Small),
            );
    }

    private function intro(): UIElement
    {
        return UI::column()
            ->gap(Unit::small())
            ->content(
                UI::text('UI Element Reference')
                    ->fontSize(FontSize::FourXL)
                    ->weight(FontWeight::Bold)
                    ->color(Color::slate(900)),
                UI::text('Every UI primitive BrickPHP ships with, grouped by purpose. Click any element to see what it does, how to use it, and runnable examples.')
                    ->fontSize(FontSize::Base)
                    ->color(Color::slate(600))
                    ->maxWidth(Unit::px(720)),
            );
    }

    private function categoryGrid(): UIElement
    {
        $cards = [];
        foreach (ElementCatalog::grouped() as $category => $docs) {
            $cards[] = $this->categoryCard($category, $docs);
        }

        return UI::grid(1)
            ->columns(2, Pseudo::md())
            ->columns(3, Pseudo::lg())
            ->gap(Unit::medium())
            ->content(...$cards);
    }

    /** @param ElementDoc[] $docs */
    private function categoryCard(string $category, array $docs): UIElement
    {
        $links = [];
        foreach ($docs as $doc) {
            $links[] = UI::button($doc->name)
                ->borderNone()
                ->background(Color::transparent())
                ->color(Color::slate(700))
                ->color(Color::red(700), Pseudo::hover())
                ->paddingX(Unit::xs())
                ->paddingY(Unit::tick(1))
                ->fontSize(FontSize::Small)
                ->clickable()
                ->onClick(fn() => Router::navigate((new ElementRoute($doc->slug))->toUrl()));
        }

        return UI::column()
            ->background(Color::white())
            ->padding(Unit::medium())
            ->rounded(Unit::roundedXl())
            ->shadow(Shadow::Small)
            ->gap(Unit::small())
            ->content(
                UI::text($category)
                    ->fontSize(FontSize::Large)
                    ->weight(FontWeight::SemiBold)
                    ->color(Color::slate(900)),
                UI::column()->gap(Unit::tick(1))->alignLeft()->content(...$links),
            );
    }
}
