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
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;

/**
 * News front-page clone — header nav, featured article,
 * "What's Next" strip, latest news list, Industry News sidebar, footer.
 *
 * Routing is delegated to the framework Router using typed BaseRoute
 * subclasses:
 *
 *   NewsListRoute        → list view at /
 *   ArticleRoute($slug)  → detail view at /article/<slug>
 *
 * Click handlers call Router::navigate(new ArticleRoute(...)) — the URL
 * derivation lives on the route class, not at the call site.
 */
class News extends Component
{
    protected function build(): VNode
    {
        // Outer column fills at least the viewport, so when the routed content
        // is short the grow()'d inner column expands and pushes the footer to
        // the bottom of the screen. When content is taller than the viewport
        // the page scrolls and the footer sits below it naturally.
        return UI::column()
            ->minHeight(Unit::vh(100))
            ->width(Unit::full())
            ->clipX()
            ->background(Color::gray(50))
            ->content(
                new Header(),

                UI::column()
                    ->grow()
                    ->width(Unit::full())
                    ->minWidth(Unit::none())
                    ->content(
                        Router::router()
                            ->register(NewsListRoute::class, fn() => $this->body())
                            ->register(ArticleRoute::class, function (ArticleRoute $route) {
                                $article = NewsData::findBySlug($route->slug);
                                if ($article !== null) {
                                    return new DetailView($article);
                                }
                                // Visible "not found" so a route-vs-data mismatch
                                // doesn't masquerade as a successful 0-patch diff
                                // against the main page (which silently leaves
                                // the article on screen on a SPA back-nav).
                                return UI::column()
                                    ->padding(Unit::extraLarge())
                                    ->alignCenter()
                                    ->gap(Unit::medium())
                                    ->content(
                                        UI::text('Article not found')
                                            ->fontSize(FontSize::TwoXL)
                                            ->weight(FontWeight::Bold)
                                            ->color(Color::gray(900)),
                                        UI::text($route->slug)
                                            ->fontSize(FontSize::Small)
                                            ->color(Color::gray(500)),
                                    );
                            })
                            ->fallback($this->body()),
                    ),

                new Footer(),
            );
    }

    // ============================================================
    // Body — 2-column grid (articles + sidebar)
    // ============================================================

    private function body(): UIElement
    {
        // Stacks vertically on small screens; switches to side-by-side
        // (main + sidebar) at lg (>=1024px).
        return UI::row()
            ->direction(Direction::column())
            ->direction(Direction::row(), Pseudo::lg())
            ->maxWidth(Unit::px(1200))
            ->width(Unit::full())
            ->marginX(Unit::auto())
            ->padding(x: Unit::rem(1),   y: Unit::rem(1.25))
            ->padding(x: Unit::rem(1.5), y: Unit::rem(2), pseudo: Pseudo::md())
            ->gap(Unit::rem(1.5))
            ->gap(Unit::rem(2), Pseudo::md())
            ->alignTop()
            ->content(
                // Main column. minWidth(none) overrides the flex-item
                // default of min-width:auto so the scrollable What's Next
                // strip can scroll inside the column instead of pushing
                // the page wider on narrow viewports.
                UI::column()
                    ->grow(2)
                    ->width(Unit::full())
                    ->minWidth(Unit::none())
                    ->gap(Unit::rem(1.5))
                    ->gap(Unit::rem(2), Pseudo::md())
                    ->content(
                        new FeaturedArticle(NewsData::featured()),
                        new StoryMap(),
                        $this->whatsNextStrip(NewsData::whatsNext()),
                        $this->articleList(NewsData::articles()),
                    ),

                // Sidebar — hidden below md; full width on md, constrained at lg.
                UI::column()
                    ->hidden()
                    ->flex(Pseudo::md())
                    ->grow(1)
                    ->width(Unit::full())
                    ->minWidth(Unit::none())
                    ->minWidth(Unit::px(280), Pseudo::lg())
                    ->maxWidth(Unit::px(340), Pseudo::lg())
                    ->gap(Unit::rem(1.5))
                    ->content(
                        $this->industryNewsSidebar(NewsData::industryNews()),
                    )
            );
    }

    // ============================================================
    // What's Next horizontal strip
    // ============================================================

    /** @param WhatsNextItem[] $items */
    private function whatsNextStrip(array $items): UIElement
    {
        $cards = array_map(fn(WhatsNextItem $i) => new WhatsNextCard($i), $items);

        return UI::column()
            ->gap(Unit::rem(1))
            ->content(
                $this->sectionHeading('WHAT\'S NEXT'),
                UI::row()
                    ->gap(Unit::rem(1))
                    ->scrollable()
                    ->paddingBottom(Unit::rem(0.5))
                    ->content(...$cards)
            );
    }

    // ============================================================
    // Article list
    // ============================================================

    /** @param Article[] $articles */
    private function articleList(array $articles): UIElement
    {
        $cards = array_map(fn(Article $a) => new ArticleCard($a), $articles);

        return UI::column()
            ->gap(Unit::rem(1))
            ->content(
                $this->sectionHeading('LATEST NEWS'),
                ...$cards
            );
    }

    // ============================================================
    // Industry News sidebar
    // ============================================================

    /** @param IndustryNewsItem[] $items */
    private function industryNewsSidebar(array $items): UIElement
    {
        $last = count($items) - 1;
        $rows = [];
        foreach ($items as $i => $item) {
            $rows[] = $this->industryNewsRow($item, $i === $last);
        }

        return UI::column()
            ->background(Color::white())
            ->rounded(Unit::rem(0.5))
            ->overflow()
            ->shadow(Shadow::Small)
            ->content(
                UI::container()
                    ->background(Color::red(600))
                    ->padding(x: Unit::rem(1), y: Unit::rem(0.75))
                    ->content(
                        UI::text('INDUSTRY NEWS')
                            ->fontSize(FontSize::Small)
                            ->weight(FontWeight::Bold)
                            ->color(Color::white())
                    ),
                UI::column()->content(...$rows)
            );
    }

    private function industryNewsRow(IndustryNewsItem $item, bool $isLast): UIElement
    {
        $row = UI::column()
            ->padding(x: Unit::rem(1), y: Unit::rem(0.75))
            ->gap(Unit::rem(0.25))
            ->clickable()
            ->background(Color::gray(50), Pseudo::hover())
            ->content(
                UI::text($item->title)
                    ->fontSize(FontSize::Small)
                    ->weight(FontWeight::Medium)
                    ->color(Color::gray(800))
                    ->color(Color::red(600), Pseudo::hover()),
                UI::text($item->formattedDate())
                    ->fontSize(FontSize::ExtraSmall)
                    ->color(Color::gray(500))
            );

        if (!$isLast) {
            $row = $row->borderBottom(1)->borderColor(Color::gray(200));
        }

        return $row;
    }

    // ============================================================
    // Shared helpers
    // ============================================================

    private function sectionHeading(string $label): UIElement
    {
        return UI::row()
            ->alignMiddle()
            ->paddingBottom(Unit::rem(0.5))
            ->borderBottom(2)
            ->borderColor(Color::red(600))
            ->content(
                UI::text($label)
                    ->fontSize(FontSize::Small)
                    ->weight(FontWeight::Bold)
                    ->color(Color::gray(900))
            );
    }

    public static function categoryLabel(string $category): UIElement
    {
        return UI::text(strtoupper($category))
            ->fontSize(FontSize::ExtraSmall)
            ->weight(FontWeight::Bold)
            ->color(self::categoryColor($category));
    }

    public static function articleMeta(string $date): UIElement
    {
        return UI::text($date)
            ->fontSize(FontSize::ExtraSmall)
            ->color(Color::gray(500));
    }

    public static function categoryColor(string $category): Color
    {
        return match (strtolower($category)) {
            'banking', 'business' => Color::blue(600),
            'mobile', 'cellular' => Color::violet(600),
            'internet', 'fibre'   => Color::cyan(600),
            'cybersecurity'       => Color::red(600),
            'cloud'               => Color::indigo(600),
            'startups', 'funding' => Color::emerald(600),
            'government'          => Color::amber(700),
            'energy'              => Color::orange(600),
            'ai', 'telecoms'      => Color::purple(600),
            default               => Color::gray(600),
        };
    }

}
