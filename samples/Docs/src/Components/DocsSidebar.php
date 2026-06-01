<?php

namespace Samples\Docs\Components;

use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\Router;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use Samples\Docs\Data\ElementCatalog;
use Samples\Docs\Data\ElementDoc;
use Samples\Docs\Routes\ElementRoute;

class DocsSidebar extends Component
{
    public function __construct(private ?string $activeSlug = null) {}

    protected function build(): VNode
    {
        $sections = [];
        foreach (ElementCatalog::grouped() as $category => $docs) {
            $sections[] = $this->section($category, $docs);
        }

        return UI::column()
            ->width(Unit::full())
            ->maxWidth(Unit::px(260))
            ->minWidth(Unit::px(220))
            ->paddingY(Unit::large())
            ->paddingX(Unit::medium())
            ->gap(Unit::large())
            ->background(Color::white())
            ->borderRight(1)
            ->borderColor(Color::slate(200))
            ->content(...$sections);
    }

    /** @param ElementDoc[] $docs */
    private function section(string $category, array $docs): UIElement
    {
        $items = [];
        foreach ($docs as $doc) {
            $items[] = $this->itemLink($doc);
        }

        return UI::column()
            ->gap(Unit::xs())
            ->content(
                UI::text(strtoupper($category))
                    ->fontSize(FontSize::ExtraSmall)
                    ->weight(FontWeight::Bold)
                    ->color(Color::slate(400)),
                UI::column()->gap(Unit::tick(1))->content(...$items),
            );
    }

    private function itemLink(ElementDoc $doc): UIElement
    {
        $active = $doc->slug === $this->activeSlug;

        $btn = UI::button($doc->name)
            ->borderNone()
            ->background(Color::transparent())
            ->paddingX(Unit::small())
            ->paddingY(Unit::xs())
            ->rounded(Unit::roundedLg())
            ->fontSize(FontSize::Small)
            ->color(Color::slate(700))
            ->background(Color::slate(100), Pseudo::hover())
            ->clickable()
            ->onClick(fn() => Router::navigate((new ElementRoute($doc->slug))->toUrl()));

        if ($active) {
            $btn = $btn
                ->background(Color::red(50))
                ->color(Color::red(700))
                ->weight(FontWeight::SemiBold);
        }

        return $btn;
    }
}
