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
use Closure;
use Samples\Docs\Components\DocsSidebar;
use Samples\Docs\Data\ElementCatalog;
use Samples\Docs\Data\ElementDoc;
use Samples\Docs\Routes\ElementRoute;

/**
 * Generic per-element page. Renders header + description + examples + see-also
 * for any ElementDoc handed to it via the constructor.
 */
class ElementDocPage extends Component
{
    public function __construct(private ElementDoc $doc) {}

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
                    ->content(new DocsSidebar($this->doc->slug)),
                UI::column()
                    ->grow()
                    ->maxWidth(Unit::px(900))
                    ->padding(Unit::large())
                    ->gap(Unit::large())
                    ->content(
                        $this->header(),
                        $this->description(),
                        $this->examples(),
                        $this->seeAlso(),
                    ),
            );
    }

    private function header(): UIElement
    {
        return UI::column()
            ->gap(Unit::small())
            ->content(
                UI::text($this->doc->category)
                    ->fontSize(FontSize::ExtraSmall)
                    ->weight(FontWeight::Bold)
                    ->color(Color::red(600)),
                UI::text($this->doc->name)
                    ->fontSize(FontSize::FourXL)
                    ->weight(FontWeight::Bold)
                    ->color(Color::slate(900)),
                UI::text($this->doc->summary)
                    ->fontSize(FontSize::Large)
                    ->color(Color::slate(600)),
                UI::row()
                    ->background(Color::slate(900))
                    ->paddingX(Unit::medium())
                    ->paddingY(Unit::small())
                    ->rounded(Unit::roundedLg())
                    ->content(
                        UI::code($this->doc->factory)
                            ->color(Color::emerald(300))
                            ->fontSize(FontSize::Small),
                    ),
            );
    }

    private function description(): UIElement
    {
        return UI::column()
            ->gap(Unit::small())
            ->content(
                UI::text('Description')
                    ->fontSize(FontSize::Large)
                    ->weight(FontWeight::SemiBold)
                    ->color(Color::slate(900)),
                UI::text($this->doc->description)
                    ->fontSize(FontSize::Base)
                    ->color(Color::slate(700)),
            );
    }

    private function examples(): UIElement
    {
        if ($this->doc->examples === []) {
            return UI::column()->content();
        }

        $blocks = [];
        foreach ($this->doc->examples as $i => $example) {
            $blocks[] = $this->exampleBlock($example, $i + 1);
        }

        return UI::column()
            ->gap(Unit::medium())
            ->content(
                UI::text(count($blocks) > 1 ? 'Examples' : 'Example')
                    ->fontSize(FontSize::Large)
                    ->weight(FontWeight::SemiBold)
                    ->color(Color::slate(900)),
                ...$blocks,
            );
    }

    /** @param array{caption?:string, code:string, render?:\Closure} $example */
    private function exampleBlock(array $example, int $n): UIElement
    {
        $caption = $example['caption'] ?? "Example {$n}";

        $sections = [
            UI::row()
                ->background(Color::slate(50))
                ->borderBottom(1)
                ->borderColor(Color::slate(200))
                ->paddingX(Unit::medium())
                ->paddingY(Unit::small())
                ->content(
                    UI::text($caption)
                        ->fontSize(FontSize::Small)
                        ->weight(FontWeight::Medium)
                        ->color(Color::slate(700)),
                ),
        ];

        if (isset($example['render'])) {
            $sections[] = $this->previewPanel($example['render']);
        }

        $sections[] = UI::pre()
            ->background(Color::slate(900))
            ->paddingX(Unit::large())
            ->paddingY(Unit::medium())
            ->overflow()
            ->content(
                UI::code($example['code'])
                    ->color(Color::slate(100))
                    ->fontSize(FontSize::Small),
            );

        return UI::column()
            ->rounded(Unit::roundedLg())
            ->overflow()
            ->shadow(Shadow::Small)
            ->bordered()
            ->borderColor(Color::slate(200))
            ->content(...$sections);
    }

    /**
     * Live preview panel — runs the example closure and embeds its returned
     * UI element inside a bordered, checkered "canvas" so the reader can see
     * what the code produces. Failures render as an inline error caption so
     * one broken closure doesn't take down the whole page.
     */
    private function previewPanel(\Closure $render): UIElement
    {
        try {
            $node = $render();
            $body = $node instanceof UIElement || $node instanceof VNode
                ? $node
                : UI::text((string) $node);
        } catch (\Throwable $e) {
            $body = UI::text('Preview unavailable: ' . $e->getMessage())
                ->fontSize(FontSize::Small)
                ->color(Color::red(700));
        }

        return UI::column()
            ->background(Color::slate(50))
            ->borderBottom(1)
            ->borderColor(Color::slate(200))
            ->padding(Unit::medium())
            ->content(
                UI::row()
                    ->paddingBottom(Unit::xs())
                    ->content(
                        UI::text('PREVIEW')
                            ->fontSize(FontSize::ExtraSmall)
                            ->weight(FontWeight::Bold)
                            ->color(Color::slate(400)),
                    ),
                UI::column()
                    ->background(Color::white())
                    ->padding(Unit::medium())
                    ->rounded(Unit::roundedLg())
                    ->bordered()
                    ->borderColor(Color::slate(200))
                    ->content($body),
            );
    }

    private function seeAlso(): UIElement
    {
        if ($this->doc->relatedSlugs === []) {
            return UI::column()->content();
        }

        $chips = [];
        foreach ($this->doc->relatedSlugs as $slug) {
            $related = ElementCatalog::find($slug);
            if ($related === null) {
                continue;
            }
            $chips[] = $this->chip($related);
        }

        if ($chips === []) {
            return UI::column()->content();
        }

        return UI::column()
            ->gap(Unit::small())
            ->content(
                UI::text('See also')
                    ->fontSize(FontSize::Large)
                    ->weight(FontWeight::SemiBold)
                    ->color(Color::slate(900)),
                UI::row()->gap(Unit::small())->content(...$chips),
            );
    }

    private function chip(ElementDoc $doc): UIElement
    {
        return UI::button($doc->name)
            ->borderNone()
            ->background(Color::slate(100))
            ->color(Color::slate(800))
            ->paddingX(Unit::medium())
            ->paddingY(Unit::xs())
            ->rounded(Unit::roundedFull())
            ->fontSize(FontSize::Small)
            ->background(Color::red(50), Pseudo::hover())
            ->color(Color::red(700), Pseudo::hover())
            ->clickable()
            ->onClick(fn() => Router::navigate((new ElementRoute($doc->slug))->toUrl()));
    }
}
