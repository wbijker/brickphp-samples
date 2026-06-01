<?php

namespace Samples\Docs\Data;

use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\Svg;
use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use Closure;

/**
 * Registry of every documented UI element. Source of truth for the API
 * sidebar and per-element pages. Each example carries both a `code` string
 * (shown verbatim) and a `render` closure (executed live for the preview
 * panel) — keep them in sync.
 */
class ElementCatalog
{
    /** @return ElementDoc[] */
    public static function all(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $cache = array_merge(
            self::layout(),
            self::semantic(),
            self::content(),
            self::forms(),
            self::lists(),
            self::tables(),
            self::media(),
            self::inlineText(),
            self::misc(),
            self::presets(),
            self::svg(),
        );
        return $cache;
    }

    public static function find(string $slug): ?ElementDoc
    {
        foreach (self::all() as $doc) {
            if ($doc->slug === $slug) {
                return $doc;
            }
        }
        return null;
    }

    /** @return array<string, ElementDoc[]> Category => entries */
    public static function grouped(): array
    {
        $out = [];
        foreach (self::all() as $doc) {
            $out[$doc->category][] = $doc;
        }
        return $out;
    }

    /**
     * Pair a code string with a render closure for one example. The code
     * is shown verbatim; the closure produces the live preview. Authors
     * keep them in sync — there's no extraction magic.
     *
     * @return array{caption:string, code:string, render?:Closure}
     */
    private static function ex(string $caption, string $code, ?Closure $render = null): array
    {
        $out = ['caption' => $caption, 'code' => $code];
        if ($render !== null) {
            $out['render'] = $render;
        }
        return $out;
    }

    // ============================================================
    // Layout
    // ============================================================

    /** @return ElementDoc[] */
    private static function layout(): array
    {
        return [
            new ElementDoc(
                'column', 'Column', 'UI::column()', 'Layout',
                'Vertical flex container — stacks children top-to-bottom.',
                'Column is the workhorse vertical layout. It puts its children in a flex column and exposes spacing (`gap`), padding, alignment (`alignCenter`, `alignMiddle`, `alignBetween`) and sizing (`width`, `height`, `grow`) helpers. Reach for it whenever you want a vertical stack.',
                [
                    self::ex('Simple stack with spacing',
                        <<<'CODE'
                        UI::column()
                            ->gap(Unit::medium())
                            ->content(
                                UI::text('First'),
                                UI::text('Second'),
                                UI::text('Third'),
                            )
                        CODE,
                        fn() => UI::column()->gap(Unit::medium())->content(
                            UI::text('First'),
                            UI::text('Second'),
                            UI::text('Third'),
                        ),
                    ),
                    self::ex('Center children horizontally',
                        <<<'CODE'
                        UI::column()
                            ->alignCenter()
                            ->padding(Unit::large())
                            ->content(UI::text('Centered'))
                        CODE,
                        fn() => UI::column()->alignCenter()->padding(Unit::large())->content(UI::text('Centered')),
                    ),
                    self::ex('Spread items vertically with alignBetween',
                        <<<'CODE'
                        UI::column()
                            ->height(Unit::px(140))
                            ->padding(Unit::small())
                            ->alignBetween()
                            ->background(Color::slate(100))
                            ->content(
                                UI::text('Top'),
                                UI::text('Bottom'),
                            )
                        CODE,
                        fn() => UI::column()
                            ->height(Unit::px(140))
                            ->padding(Unit::small())
                            ->alignBetween()
                            ->background(Color::slate(100))
                            ->content(
                                UI::text('Top'),
                                UI::text('Bottom'),
                            ),
                    ),
                ],
                ['row', 'stack', 'grid'],
            ),

            new ElementDoc(
                'row', 'Row', 'UI::row()', 'Layout',
                'Horizontal flex container — lays children out left-to-right.',
                'Row is the horizontal counterpart to Column. Combine `alignMiddle()` (cross-axis) with `alignBetween()` / `alignCenter()` (main-axis) for typical nav-bar and toolbar patterns. Use `gap()` for spacing between items.',
                [
                    self::ex('Toolbar layout',
                        <<<'CODE'
                        UI::row()
                            ->alignMiddle()
                            ->alignBetween()
                            ->paddingX(Unit::large())
                            ->content(
                                UI::text('Logo'),
                                UI::row()->gap(Unit::small())->content(
                                    UI::button('Sign in'),
                                    UI::button('Sign up'),
                                ),
                            )
                        CODE,
                        fn() => UI::row()
                            ->alignMiddle()
                            ->alignBetween()
                            ->paddingX(Unit::large())
                            ->content(
                                UI::text('Logo'),
                                UI::row()->gap(Unit::small())->content(
                                    UI::button('Sign in'),
                                    UI::button('Sign up'),
                                ),
                            ),
                    ),
                    self::ex('Even gap with centered children',
                        <<<'CODE'
                        UI::row()
                            ->gap(Unit::medium())
                            ->alignCenter()
                            ->alignMiddle()
                            ->content(
                                UI::badge('one'),
                                UI::badge('two'),
                                UI::badge('three'),
                            )
                        CODE,
                        fn() => UI::row()
                            ->gap(Unit::medium())
                            ->alignCenter()
                            ->alignMiddle()
                            ->content(
                                UI::badge('one'),
                                UI::badge('two'),
                                UI::badge('three'),
                            ),
                    ),
                    self::ex('Flexible spacer between sides',
                        <<<'CODE'
                        UI::row()
                            ->alignMiddle()
                            ->width(Unit::full())
                            ->content(
                                UI::text('Left'),
                                UI::spacer(),
                                UI::text('Right'),
                            )
                        CODE,
                        fn() => UI::row()
                            ->alignMiddle()
                            ->width(Unit::full())
                            ->content(
                                UI::text('Left'),
                                UI::spacer(),
                                UI::text('Right'),
                            ),
                    ),
                ],
                ['column', 'inlined'],
            ),

            new ElementDoc(
                'layers', 'Layers', 'UI::layers()', 'Layout',
                'Stacks children on top of each other (z-axis).',
                'Layers gives you a positioning context where each child fills the same box. Useful for overlays — loading spinners on top of content, badges over avatars, anything that needs to sit on top of something else.',
                [
                    self::ex('Overlay a badge on a colored block',
                        <<<'CODE'
                        UI::layers()->content(
                            UI::container()
                                ->width(Unit::px(120))
                                ->height(Unit::px(60))
                                ->background(Color::slate(800)),
                            UI::badge('NEW')
                                ->background(Color::red(500))
                                ->color(Color::white()),
                        )
                        CODE,
                        fn() => UI::layers()->content(
                            UI::container()
                                ->width(Unit::px(120))
                                ->height(Unit::px(60))
                                ->background(Color::slate(800)),
                            UI::badge('NEW')
                                ->background(Color::red(500))
                                ->color(Color::white()),
                        ),
                    ),
                    self::ex('Centered text over a swatch',
                        <<<'CODE'
                        UI::layers()
                            ->width(Unit::px(160))
                            ->height(Unit::px(80))
                            ->content(
                                UI::container()
                                    ->width(Unit::px(160))
                                    ->height(Unit::px(80))
                                    ->background(Color::emerald(400)),
                                UI::center()
                                    ->width(Unit::px(160))
                                    ->height(Unit::px(80))
                                    ->content(
                                        UI::text('SALE')
                                            ->color(Color::white())
                                            ->weight(FontWeight::Bold),
                                    ),
                            )
                        CODE,
                        fn() => UI::layers()
                            ->width(Unit::px(160))
                            ->height(Unit::px(80))
                            ->content(
                                UI::container()
                                    ->width(Unit::px(160))
                                    ->height(Unit::px(80))
                                    ->background(Color::emerald(400)),
                                UI::center()
                                    ->width(Unit::px(160))
                                    ->height(Unit::px(80))
                                    ->content(
                                        UI::text('SALE')
                                            ->color(Color::white())
                                            ->weight(FontWeight::Bold),
                                    ),
                            ),
                    ),
                ],
                ['stack', 'column'],
            ),

            new ElementDoc(
                'stack', 'Stack', 'UI::stack()', 'Layout',
                'Z-stacked container — children positioned via Position wrappers.',
                'Stack creates a positioning context (`position: relative`); each child must be a `Stack::position()` element which absolutely positions itself with `top`/`right`/`bottom`/`left`. Useful for explicit overlay layouts.',
                [
                    self::ex('Absolute corners inside a box',
                        <<<'CODE'
                        UI::stack()
                            ->width(Unit::px(160))
                            ->height(Unit::px(80))
                            ->background(Color::slate(100))
                            ->content(
                                \BrickPHP\UI\Stack::position(UI::badge('TL'))
                                    ->top(Unit::px(8))->left(Unit::px(8)),
                                \BrickPHP\UI\Stack::position(UI::badge('BR'))
                                    ->bottom(Unit::px(8))->right(Unit::px(8)),
                            )
                        CODE,
                        fn() => UI::stack()
                            ->width(Unit::px(160))
                            ->height(Unit::px(80))
                            ->background(Color::slate(100))
                            ->content(
                                \BrickPHP\UI\Stack::position(UI::badge('TL'))
                                    ->top(Unit::px(8))->left(Unit::px(8)),
                                \BrickPHP\UI\Stack::position(UI::badge('BR'))
                                    ->bottom(Unit::px(8))->right(Unit::px(8)),
                            ),
                    ),
                    self::ex('Single child filling the parent',
                        <<<'CODE'
                        UI::stack()
                            ->width(Unit::px(120))
                            ->height(Unit::px(80))
                            ->background(Color::slate(200))
                            ->content(
                                \BrickPHP\UI\Stack::position(
                                    UI::center()->content(UI::text('Hello')),
                                )->fillParent(),
                            )
                        CODE,
                        fn() => UI::stack()
                            ->width(Unit::px(120))
                            ->height(Unit::px(80))
                            ->background(Color::slate(200))
                            ->content(
                                \BrickPHP\UI\Stack::position(
                                    UI::center()->content(UI::text('Hello')),
                                )->fillParent(),
                            ),
                    ),
                ],
                ['layers'],
            ),

            new ElementDoc(
                'grid', 'Grid', 'UI::grid(?int $columns)', 'Layout',
                'CSS Grid container — for two-dimensional layouts.',
                'Grid wraps CSS Grid with a fluent API. Set the column count with the constructor, or use `columns()`, `rows()`, `templateColumns()`, and `gap()` to shape the grid. Combine with `Pseudo::md()` / `Pseudo::lg()` for responsive layouts.',
                [
                    self::ex('Three-column grid',
                        <<<'CODE'
                        UI::grid(3)
                            ->gap(Unit::small())
                            ->content(
                                UI::card()->content(UI::text('A')),
                                UI::card()->content(UI::text('B')),
                                UI::card()->content(UI::text('C')),
                            )
                        CODE,
                        fn() => UI::grid(3)->gap(Unit::small())->content(
                            UI::card()->content(UI::text('A')),
                            UI::card()->content(UI::text('B')),
                            UI::card()->content(UI::text('C')),
                        ),
                    ),
                    self::ex('Two-column with separate row/column gaps',
                        <<<'CODE'
                        UI::grid(2)
                            ->gapX(Unit::large())
                            ->gapY(Unit::small())
                            ->content(
                                UI::badge('1'), UI::badge('2'),
                                UI::badge('3'), UI::badge('4'),
                            )
                        CODE,
                        fn() => UI::grid(2)
                            ->gapX(Unit::large())
                            ->gapY(Unit::small())
                            ->content(
                                UI::badge('1'), UI::badge('2'),
                                UI::badge('3'), UI::badge('4'),
                            ),
                    ),
                ],
                ['grid-item', 'column'],
            ),

            new ElementDoc(
                'grid-item', 'GridItem', 'UI::gridItem(...)', 'Layout',
                'A single cell inside a Grid that can span columns or rows.',
                'Most Grid children sit in their default cell. Use GridItem only when you need to span multiple columns/rows or place a child explicitly.',
                [
                    self::ex('Span two columns',
                        <<<'CODE'
                        UI::grid(3)
                            ->gap(Unit::small())
                            ->content(
                                UI::gridItem(UI::card()->content(UI::text('Wide header')))
                                    ->colSpan(2),
                                UI::card()->content(UI::text('Side')),
                            )
                        CODE,
                        fn() => UI::grid(3)->gap(Unit::small())->content(
                            UI::gridItem(UI::card()->content(UI::text('Wide header')))
                                ->colSpan(2),
                            UI::card()->content(UI::text('Side')),
                        ),
                    ),
                    self::ex('Span two rows',
                        <<<'CODE'
                        UI::grid(3)
                            ->gap(Unit::small())
                            ->content(
                                UI::gridItem(
                                    UI::card()
                                        ->height(Unit::px(120))
                                        ->content(UI::text('Tall')),
                                )->rowSpan(2),
                                UI::card()->content(UI::text('A')),
                                UI::card()->content(UI::text('B')),
                                UI::card()->content(UI::text('C')),
                                UI::card()->content(UI::text('D')),
                            )
                        CODE,
                        fn() => UI::grid(3)->gap(Unit::small())->content(
                            UI::gridItem(
                                UI::card()
                                    ->height(Unit::px(120))
                                    ->content(UI::text('Tall')),
                            )->rowSpan(2),
                            UI::card()->content(UI::text('A')),
                            UI::card()->content(UI::text('B')),
                            UI::card()->content(UI::text('C')),
                            UI::card()->content(UI::text('D')),
                        ),
                    ),
                ],
                ['grid'],
            ),

            new ElementDoc(
                'inlined', 'Inlined', 'UI::inlined()', 'Layout',
                'Inline-flex container — flows with surrounding text.',
                'Use Inlined when you want a flex container that participates in the inline flow, instead of taking up a full block. Otherwise it behaves like Row.',
                [
                    self::ex('Heads-up inline group',
                        <<<'CODE'
                        UI::inlined()->spacing(Unit::xs())->content(
                            UI::text('Heads up:'),
                            UI::badge('Beta'),
                        )
                        CODE,
                        fn() => UI::inlined()->spacing(Unit::xs())->content(
                            UI::text('Heads up:'),
                            UI::badge('Beta'),
                        ),
                    ),
                    self::ex('Wrapping tag list',
                        <<<'CODE'
                        UI::inlined()->spacing(Unit::xs())->content(
                            UI::badge('php'),
                            UI::badge('framework'),
                            UI::badge('server-side'),
                            UI::badge('reactive'),
                            UI::badge('no-build'),
                        )
                        CODE,
                        fn() => UI::inlined()->spacing(Unit::xs())->content(
                            UI::badge('php'),
                            UI::badge('framework'),
                            UI::badge('server-side'),
                            UI::badge('reactive'),
                            UI::badge('no-build'),
                        ),
                    ),
                ],
                ['row'],
            ),

            new ElementDoc(
                'unconstrained', 'Unconstrained', 'UI::unconstrained()', 'Layout',
                'Container that ignores parent width constraints.',
                'Drops out of the parent\'s max-width restriction — useful for elements like full-bleed banners or hero images that should stretch edge-to-edge inside a centered, max-width layout.',
                [
                    self::ex('Full-bleed band inside a max-width column',
                        <<<'CODE'
                        UI::column()->maxWidth(Unit::px(360))->content(
                            UI::text('Article body...'),
                            UI::unconstrained()->content(
                                UI::container()
                                    ->height(Unit::px(40))
                                    ->background(Color::red(400)),
                            ),
                            UI::text('More body...'),
                        )
                        CODE,
                        fn() => UI::column()->maxWidth(Unit::px(360))->content(
                            UI::text('Article body...'),
                            UI::unconstrained()->content(
                                UI::container()
                                    ->height(Unit::px(40))
                                    ->background(Color::red(400)),
                            ),
                            UI::text('More body...'),
                        ),
                    ),
                    self::ex('Bleed background inside a card',
                        <<<'CODE'
                        UI::card()
                            ->maxWidth(Unit::px(280))
                            ->content(
                                UI::unconstrained()->content(
                                    UI::container()
                                        ->height(Unit::px(60))
                                        ->background(Color::indigo(500)),
                                ),
                                UI::text('Card title')->weight(FontWeight::Bold),
                                UI::text('Body inside the card.'),
                            )
                        CODE,
                        fn() => UI::card()
                            ->maxWidth(Unit::px(280))
                            ->content(
                                UI::unconstrained()->content(
                                    UI::container()
                                        ->height(Unit::px(60))
                                        ->background(Color::indigo(500)),
                                ),
                                UI::text('Card title')->weight(FontWeight::Bold),
                                UI::text('Body inside the card.'),
                            ),
                    ),
                ],
                ['container'],
            ),

            new ElementDoc(
                'multi-column', 'MultiColumn', 'UI::multiColumn(int $count = 2)', 'Layout',
                'CSS multi-column container — flows children across N text columns.',
                'Different from `Grid`: MultiColumn lays content out the way newspaper text flows — first column fills top-to-bottom, then continues in the second. Ideal for long-form text or large lists.',
                [
                    self::ex('Two-column prose',
                        <<<'CODE'
                        UI::multiColumn(2)
                            ->gap(Unit::large())
                            ->content(UI::text(str_repeat('Lorem ipsum dolor sit amet. ', 12)))
                        CODE,
                        fn() => UI::multiColumn(2)
                            ->gap(Unit::large())
                            ->content(UI::text(str_repeat('Lorem ipsum dolor sit amet. ', 12))),
                    ),
                    self::ex('Three-column ingredient list',
                        <<<'CODE'
                        UI::multiColumn(3)
                            ->gap(Unit::medium())
                            ->content(UI::ul()->items(
                                UI::li('Flour'),
                                UI::li('Sugar'),
                                UI::li('Eggs'),
                                UI::li('Butter'),
                                UI::li('Salt'),
                                UI::li('Yeast'),
                            ))
                        CODE,
                        fn() => UI::multiColumn(3)
                            ->gap(Unit::medium())
                            ->content(UI::ul()->items(
                                UI::li('Flour'),
                                UI::li('Sugar'),
                                UI::li('Eggs'),
                                UI::li('Butter'),
                                UI::li('Salt'),
                                UI::li('Yeast'),
                            )),
                    ),
                ],
                ['grid'],
            ),

            new ElementDoc(
                'container', 'Container', 'UI::container()', 'Layout',
                'Plain `<div>` with the full UIElement API.',
                'The most generic element. Reach for it when you want a styled box that isn\'t a row or column — for example a colored rectangle, a divider, a custom-positioned wrapper, or any element you intend to extend with other props.',
                [
                    self::ex('A green dot',
                        <<<'CODE'
                        UI::container()
                            ->width(Unit::px(40))
                            ->height(Unit::px(40))
                            ->roundedFull()
                            ->background(Color::emerald(500))
                        CODE,
                        fn() => UI::container()
                            ->width(Unit::px(40))
                            ->height(Unit::px(40))
                            ->roundedFull()
                            ->background(Color::emerald(500)),
                    ),
                    self::ex('Shadowed coloured panel',
                        <<<'CODE'
                        UI::container()
                            ->padding(Unit::medium())
                            ->rounded(Unit::roundedLg())
                            ->background(Color::indigo(50))
                            ->shadow(Shadow::Medium)
                            ->content(
                                UI::text('Heads up — info panel.')
                                    ->color(Color::indigo(800)),
                            )
                        CODE,
                        fn() => UI::container()
                            ->padding(Unit::medium())
                            ->rounded(Unit::roundedLg())
                            ->background(Color::indigo(50))
                            ->shadow(Shadow::Medium)
                            ->content(
                                UI::text('Heads up — info panel.')
                                    ->color(Color::indigo(800)),
                            ),
                    ),
                ],
                ['column', 'row', 'div'],
            ),
        ];
    }

    // ============================================================
    // Semantic
    // ============================================================

    /** @return ElementDoc[] */
    private static function semantic(): array
    {
        return [
            new ElementDoc(
                'section', 'Section', 'UI::section()', 'Semantic',
                'Renders a `<section>` — for thematic groupings of content.',
                'Use Section when you want an accessible, semantic block (a chapter, an "About us" segment, etc.). Behaves like Container but emits `<section>` so screen readers and search engines understand the structure.',
                [
                    self::ex('Section block',
                        <<<'CODE'
                        UI::section()
                            ->padding(Unit::medium())
                            ->background(Color::slate(100))
                            ->content(
                                UI::text('About')->fontSize(FontSize::Large)->weight(FontWeight::Bold),
                                UI::text('We build with bricks.'),
                            )
                        CODE,
                        fn() => UI::section()
                            ->padding(Unit::medium())
                            ->background(Color::slate(100))
                            ->content(
                                UI::text('About')->fontSize(FontSize::Large)->weight(FontWeight::Bold),
                                UI::text('We build with bricks.'),
                            ),
                    ),
                    self::ex('Section with bordered accent',
                        <<<'CODE'
                        UI::section()
                            ->padding(Unit::medium())
                            ->borderLeft(4)
                            ->borderColor(Color::red(500))
                            ->background(Color::white())
                            ->content(
                                UI::text('Highlighted callout')
                                    ->weight(FontWeight::SemiBold),
                            )
                        CODE,
                        fn() => UI::section()
                            ->padding(Unit::medium())
                            ->borderLeft(4)
                            ->borderColor(Color::red(500))
                            ->background(Color::white())
                            ->content(
                                UI::text('Highlighted callout')
                                    ->weight(FontWeight::SemiBold),
                            ),
                    ),
                ],
                ['header', 'footer', 'nav'],
            ),

            new ElementDoc(
                'nav', 'Nav', 'UI::nav()', 'Semantic',
                'Renders a `<nav>` — for primary site navigation.',
                'Use Nav for top navigation, sidebar menus, breadcrumbs — any group of links that helps users move through the site. Semantically meaningful for accessibility.',
                [
                    self::ex('Inline nav links',
                        <<<'CODE'
                        UI::nav()
                            ->paddingY(Unit::small())
                            ->content(
                                UI::row()->gap(Unit::medium())->content(
                                    UI::link('#', 'Home'),
                                    UI::link('#', 'About'),
                                    UI::link('#', 'Contact'),
                                ),
                            )
                        CODE,
                        fn() => UI::nav()
                            ->paddingY(Unit::small())
                            ->content(
                                UI::row()->gap(Unit::medium())->content(
                                    UI::link('#', 'Home'),
                                    UI::link('#', 'About'),
                                    UI::link('#', 'Contact'),
                                ),
                            ),
                    ),
                    self::ex('Breadcrumb nav',
                        <<<'CODE'
                        UI::nav()->content(
                            UI::row()->gap(Unit::xs())->alignMiddle()->content(
                                UI::link('#', 'Home'),
                                UI::text('›')->color(Color::slate(400)),
                                UI::link('#', 'Docs'),
                                UI::text('›')->color(Color::slate(400)),
                                UI::text('Layout')->color(Color::slate(600)),
                            ),
                        )
                        CODE,
                        fn() => UI::nav()->content(
                            UI::row()->gap(Unit::xs())->alignMiddle()->content(
                                UI::link('#', 'Home'),
                                UI::text('›')->color(Color::slate(400)),
                                UI::link('#', 'Docs'),
                                UI::text('›')->color(Color::slate(400)),
                                UI::text('Layout')->color(Color::slate(600)),
                            ),
                        ),
                    ),
                ],
                ['header'],
            ),

            new ElementDoc(
                'header', 'Header', 'UI::header()', 'Semantic',
                'Renders a `<header>` — for page or section headers.',
                'Header marks the top region of a page or section. Often holds your logo, nav, and a title.',
                [
                    self::ex('Page header',
                        <<<'CODE'
                        UI::header()
                            ->paddingY(Unit::small())
                            ->paddingX(Unit::medium())
                            ->background(Color::slate(900))
                            ->content(
                                UI::text('My Site')
                                    ->color(Color::white())
                                    ->fontSize(FontSize::Large)
                                    ->weight(FontWeight::Bold),
                            )
                        CODE,
                        fn() => UI::header()
                            ->paddingY(Unit::small())
                            ->paddingX(Unit::medium())
                            ->background(Color::slate(900))
                            ->content(
                                UI::text('My Site')
                                    ->color(Color::white())
                                    ->fontSize(FontSize::Large)
                                    ->weight(FontWeight::Bold),
                            ),
                    ),
                    self::ex('Header with title + actions',
                        <<<'CODE'
                        UI::header()
                            ->padding(Unit::medium())
                            ->background(Color::white())
                            ->shadow(Shadow::Small)
                            ->content(
                                UI::row()->alignMiddle()->alignBetween()->content(
                                    UI::text('Dashboard')->weight(FontWeight::Bold),
                                    UI::button('New')
                                        ->background(Color::red(500))
                                        ->color(Color::white())
                                        ->paddingX(Unit::medium())
                                        ->paddingY(Unit::xs())
                                        ->rounded(Unit::roundedLg())
                                        ->borderNone(),
                                ),
                            )
                        CODE,
                        fn() => UI::header()
                            ->padding(Unit::medium())
                            ->background(Color::white())
                            ->shadow(Shadow::Small)
                            ->content(
                                UI::row()->alignMiddle()->alignBetween()->content(
                                    UI::text('Dashboard')->weight(FontWeight::Bold),
                                    UI::button('New')
                                        ->background(Color::red(500))
                                        ->color(Color::white())
                                        ->paddingX(Unit::medium())
                                        ->paddingY(Unit::xs())
                                        ->rounded(Unit::roundedLg())
                                        ->borderNone(),
                                ),
                            ),
                    ),
                ],
                ['nav', 'footer'],
            ),

            new ElementDoc(
                'footer', 'Footer', 'UI::footer()', 'Semantic',
                'Renders a `<footer>` — for the bottom region of a page or section.',
                'Pair with Header to bracket page content. Holds links, copyright, contact details.',
                [
                    self::ex('Simple copyright footer',
                        <<<'CODE'
                        UI::footer()
                            ->paddingY(Unit::small())
                            ->paddingX(Unit::medium())
                            ->background(Color::slate(100))
                            ->content(
                                UI::text('© 2026')
                                    ->fontSize(FontSize::Small)
                                    ->color(Color::slate(600)),
                            )
                        CODE,
                        fn() => UI::footer()
                            ->paddingY(Unit::small())
                            ->paddingX(Unit::medium())
                            ->background(Color::slate(100))
                            ->content(
                                UI::text('© 2026')
                                    ->fontSize(FontSize::Small)
                                    ->color(Color::slate(600)),
                            ),
                    ),
                    self::ex('Footer with link row',
                        <<<'CODE'
                        UI::footer()
                            ->padding(Unit::medium())
                            ->background(Color::slate(900))
                            ->content(
                                UI::row()->gap(Unit::medium())->alignMiddle()->content(
                                    UI::text('BrickPHP')->color(Color::slate(400)),
                                    UI::spacer(),
                                    UI::link('#', 'Privacy')->color(Color::slate(300)),
                                    UI::link('#', 'Terms')->color(Color::slate(300)),
                                ),
                            )
                        CODE,
                        fn() => UI::footer()
                            ->padding(Unit::medium())
                            ->background(Color::slate(900))
                            ->content(
                                UI::row()->gap(Unit::medium())->alignMiddle()->content(
                                    UI::text('BrickPHP')->color(Color::slate(400)),
                                    UI::spacer(),
                                    UI::link('#', 'Privacy')->color(Color::slate(300)),
                                    UI::link('#', 'Terms')->color(Color::slate(300)),
                                ),
                            ),
                    ),
                ],
                ['header'],
            ),

            new ElementDoc(
                'figure', 'Figure', 'UI::figure()', 'Semantic',
                'Renders a `<figure>` — for self-contained media with an optional caption.',
                'Wrap an image, code block, or diagram together with its caption so they\'re treated as a unit.',
                [
                    self::ex('Image with caption',
                        <<<'CODE'
                        UI::figure()->content(
                            UI::container()
                                ->width(Unit::px(160))
                                ->height(Unit::px(80))
                                ->background(Color::slate(300)),
                            UI::text('Fig. 1 — placeholder')
                                ->fontSize(FontSize::Small)
                                ->color(Color::slate(500)),
                        )
                        CODE,
                        fn() => UI::figure()->content(
                            UI::container()
                                ->width(Unit::px(160))
                                ->height(Unit::px(80))
                                ->background(Color::slate(300)),
                            UI::text('Fig. 1 — placeholder')
                                ->fontSize(FontSize::Small)
                                ->color(Color::slate(500)),
                        ),
                    ),
                    self::ex('Code figure',
                        <<<'CODE'
                        UI::figure()->content(
                            UI::pre()
                                ->padding(Unit::small())
                                ->background(Color::slate(900))
                                ->color(Color::white())
                                ->rounded(Unit::roundedLg())
                                ->content(UI::code('$x = 1 + 2;')),
                            UI::text('Listing 1 — addition')
                                ->fontSize(FontSize::Small)
                                ->color(Color::slate(500)),
                        )
                        CODE,
                        fn() => UI::figure()->content(
                            UI::pre()
                                ->padding(Unit::small())
                                ->background(Color::slate(900))
                                ->color(Color::white())
                                ->rounded(Unit::roundedLg())
                                ->content(UI::code('$x = 1 + 2;')),
                            UI::text('Listing 1 — addition')
                                ->fontSize(FontSize::Small)
                                ->color(Color::slate(500)),
                        ),
                    ),
                ],
                ['image'],
            ),

            new ElementDoc(
                'details', 'Details', 'UI::details()', 'Semantic',
                'Renders a `<details>` — native, collapsible disclosure widget.',
                'Browser-native expand/collapse without JS. Pass a string as the first child to set the summary; subsequent children become the body.',
                [
                    self::ex('Disclosure widget',
                        <<<'CODE'
                        UI::details()->content(
                            UI::text('Show advanced options'),
                            UI::column()
                                ->paddingY(Unit::small())
                                ->content(UI::text('Hidden until you expand.')),
                        )
                        CODE,
                        fn() => UI::details()->content(
                            UI::text('Show advanced options'),
                            UI::column()->paddingY(Unit::small())->content(
                                UI::text('Hidden until you expand.'),
                            ),
                        ),
                    ),
                    self::ex('FAQ item with rich body',
                        <<<'CODE'
                        UI::details()
                            ->padding(Unit::small())
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->rounded(Unit::roundedLg())
                            ->content(
                                UI::text('Does BrickPHP need Node?')
                                    ->weight(FontWeight::SemiBold),
                                UI::text('No. Zero npm, zero build step.')
                                    ->color(Color::slate(600))
                                    ->paddingTop(Unit::xs()),
                            )
                        CODE,
                        fn() => UI::details()
                            ->padding(Unit::small())
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->rounded(Unit::roundedLg())
                            ->content(
                                UI::text('Does BrickPHP need Node?')
                                    ->weight(FontWeight::SemiBold),
                                UI::text('No. Zero npm, zero build step.')
                                    ->color(Color::slate(600))
                                    ->paddingTop(Unit::xs()),
                            ),
                    ),
                ],
                ['dialog'],
            ),

            new ElementDoc(
                'dialog', 'Dialog', 'UI::dialog()', 'Semantic',
                'Renders a `<dialog>` — native modal/popover element.',
                'Browser-native dialog with built-in focus trap. Toggle visibility via the `open` attribute or programmatically.',
                [
                    self::ex('Inline open dialog',
                        <<<'CODE'
                        UI::dialog()
                            ->open()
                            ->padding(Unit::medium())
                            ->content(
                                UI::text('Are you sure?'),
                                UI::row()->gap(Unit::small())->paddingTop(Unit::small())->content(
                                    UI::button('Cancel'),
                                    UI::button('OK'),
                                ),
                            )
                        CODE,
                        fn() => UI::dialog()
                            ->open()
                            ->padding(Unit::medium())
                            ->content(
                                UI::text('Are you sure?'),
                                UI::row()->gap(Unit::small())->paddingTop(Unit::small())->content(
                                    UI::button('Cancel'),
                                    UI::button('OK'),
                                ),
                            ),
                    ),
                    self::ex('Form inside a dialog',
                        <<<'CODE'
                        UI::dialog()
                            ->open()
                            ->padding(Unit::medium())
                            ->rounded(Unit::roundedLg())
                            ->content(
                                UI::text('Sign in')->weight(FontWeight::Bold),
                                UI::column()->gap(Unit::small())->paddingTop(Unit::small())->content(
                                    UI::input()
                                        ->attr('placeholder', 'Email')
                                        ->paddingX(Unit::small())
                                        ->paddingY(Unit::xs())
                                        ->bordered()
                                        ->borderColor(Color::slate(300))
                                        ->rounded(Unit::roundedLg()),
                                    UI::button('Continue'),
                                ),
                            )
                        CODE,
                        fn() => UI::dialog()
                            ->open()
                            ->padding(Unit::medium())
                            ->rounded(Unit::roundedLg())
                            ->content(
                                UI::text('Sign in')->weight(FontWeight::Bold),
                                UI::column()->gap(Unit::small())->paddingTop(Unit::small())->content(
                                    UI::input()
                                        ->attr('placeholder', 'Email')
                                        ->paddingX(Unit::small())
                                        ->paddingY(Unit::xs())
                                        ->bordered()
                                        ->borderColor(Color::slate(300))
                                        ->rounded(Unit::roundedLg()),
                                    UI::button('Continue'),
                                ),
                            ),
                    ),
                ],
                ['details'],
            ),
        ];
    }

    // ============================================================
    // Content
    // ============================================================

    /** @return ElementDoc[] */
    private static function content(): array
    {
        return [
            new ElementDoc(
                'text', 'Text', 'UI::text(string $content)', 'Content',
                'A styled text node — your go-to typography primitive.',
                'Text is the building block for every label, paragraph, heading, and inline string in your app. Style it with `fontSize()`, `weight()`, `color()`, `center()`, `italic()`.',
                [
                    self::ex('Heading-style text',
                        <<<'CODE'
                        UI::text('Welcome')
                            ->fontSize(FontSize::FourXL)
                            ->weight(FontWeight::Bold)
                            ->color(Color::slate(900))
                        CODE,
                        fn() => UI::text('Welcome')
                            ->fontSize(FontSize::FourXL)
                            ->weight(FontWeight::Bold)
                            ->color(Color::slate(900)),
                    ),
                    self::ex('Inline label',
                        <<<'CODE'
                        UI::text('Read more →')
                            ->fontSize(FontSize::Small)
                            ->color(Color::red(600))
                        CODE,
                        fn() => UI::text('Read more →')
                            ->fontSize(FontSize::Small)
                            ->color(Color::red(600)),
                    ),
                    self::ex('Italic muted caption',
                        <<<'CODE'
                        UI::text('Posted 5 minutes ago')
                            ->italic()
                            ->fontSize(FontSize::ExtraSmall)
                            ->color(Color::slate(500))
                        CODE,
                        fn() => UI::text('Posted 5 minutes ago')
                            ->italic()
                            ->fontSize(FontSize::ExtraSmall)
                            ->color(Color::slate(500)),
                    ),
                    self::ex('Uppercase tracked label',
                        <<<'CODE'
                        UI::text('PINNED')
                            ->uppercase()
                            ->fontSize(FontSize::ExtraSmall)
                            ->weight(FontWeight::Bold)
                            ->color(Color::red(700))
                        CODE,
                        fn() => UI::text('PINNED')
                            ->uppercase()
                            ->fontSize(FontSize::ExtraSmall)
                            ->weight(FontWeight::Bold)
                            ->color(Color::red(700)),
                    ),
                ],
                ['heading', 'paragraph', 'span'],
            ),

            new ElementDoc(
                'heading', 'Heading', 'UI::heading(string $content, int $level = 1)', 'Content',
                'A heading text — semantic, styled by level.',
                'Convenience factory for headings. Combine with `fontSize()` and `weight()` for visual styling.',
                [
                    self::ex('Chapter heading',
                        <<<'CODE'
                        UI::heading('Chapter 1', 1)
                            ->fontSize(FontSize::ThreeXL)
                            ->weight(FontWeight::Bold)
                        CODE,
                        fn() => UI::heading('Chapter 1', 1)
                            ->fontSize(FontSize::ThreeXL)
                            ->weight(FontWeight::Bold),
                    ),
                    self::ex('Subheading',
                        <<<'CODE'
                        UI::heading('Subsection', 2)
                            ->fontSize(FontSize::ExtraLarge)
                            ->color(Color::slate(700))
                        CODE,
                        fn() => UI::heading('Subsection', 2)
                            ->fontSize(FontSize::ExtraLarge)
                            ->color(Color::slate(700)),
                    ),
                ],
                ['text', 'paragraph'],
            ),

            new ElementDoc(
                'paragraph', 'Paragraph', 'UI::paragraph(string $content)', 'Content',
                'A body-text paragraph.',
                'Use Paragraph for prose blocks instead of Text — it gives you a semantically meaningful container for body copy.',
                [
                    self::ex('Body paragraph',
                        <<<'CODE'
                        UI::paragraph('BrickPHP makes building UIs feel like assembling Lego — typed, composable, fun.')
                            ->color(Color::slate(700))
                        CODE,
                        fn() => UI::paragraph('BrickPHP makes building UIs feel like assembling Lego — typed, composable, fun.')
                            ->color(Color::slate(700)),
                    ),
                    self::ex('Constrained reading width',
                        <<<'CODE'
                        UI::paragraph('Limit prose to ~60 characters per line for comfortable reading on wide screens.')
                            ->maxWidth(Unit::px(360))
                            ->color(Color::slate(700))
                        CODE,
                        fn() => UI::paragraph('Limit prose to ~60 characters per line for comfortable reading on wide screens.')
                            ->maxWidth(Unit::px(360))
                            ->color(Color::slate(700)),
                    ),
                ],
                ['text', 'blockquote'],
            ),

            new ElementDoc(
                'image', 'Image', 'UI::image(string $src, string $alt = "")', 'Content',
                'Renders an `<img>` element with the full UIElement style API.',
                'Always supply `$alt` (even an empty string is explicit). Use `rounded()`, `width()`, and `objectCover()` for typical avatar / cover patterns.',
                [
                    self::ex('Rounded thumbnail',
                        <<<'CODE'
                        UI::image('https://placehold.co/120x120/E11D48/fff?text=Brick', 'Demo')
                            ->width(Unit::px(120))
                            ->rounded(Unit::roundedLg())
                        CODE,
                        fn() => UI::image('https://placehold.co/120x120/E11D48/fff?text=Brick', 'Demo')
                            ->width(Unit::px(120))
                            ->rounded(Unit::roundedLg()),
                    ),
                    self::ex('Circle avatar',
                        <<<'CODE'
                        UI::image('https://placehold.co/96x96/0f172a/fff?text=Me', 'Me')
                            ->width(Unit::px(64))
                            ->height(Unit::px(64))
                            ->roundedFull()
                            ->objectCover()
                        CODE,
                        fn() => UI::image('https://placehold.co/96x96/0f172a/fff?text=Me', 'Me')
                            ->width(Unit::px(64))
                            ->height(Unit::px(64))
                            ->roundedFull()
                            ->objectCover(),
                    ),
                ],
                ['avatar', 'picture'],
            ),

            new ElementDoc(
                'button', 'Button', 'UI::button(string $label)', 'Content',
                'Interactive button. Hook up actions with `onClick()`.',
                'Buttons are the entry point for almost every user interaction. The handler runs on the server — mutate state directly, then let BrickPHP re-render and diff.',
                [
                    self::ex('Primary action',
                        <<<'CODE'
                        UI::button('Add one')
                            ->background(Color::red(500))
                            ->color(Color::white())
                            ->paddingX(Unit::medium())
                            ->paddingY(Unit::small())
                            ->rounded(Unit::roundedLg())
                            ->borderNone()
                        CODE,
                        fn() => UI::button('Add one')
                            ->background(Color::red(500))
                            ->color(Color::white())
                            ->paddingX(Unit::medium())
                            ->paddingY(Unit::small())
                            ->rounded(Unit::roundedLg())
                            ->borderNone(),
                    ),
                    self::ex('Secondary outline button',
                        <<<'CODE'
                        UI::button('Cancel')
                            ->background(Color::white())
                            ->color(Color::slate(700))
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->paddingX(Unit::medium())
                            ->paddingY(Unit::small())
                            ->rounded(Unit::roundedLg())
                            ->background(Color::slate(50), Pseudo::hover())
                        CODE,
                        fn() => UI::button('Cancel')
                            ->background(Color::white())
                            ->color(Color::slate(700))
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->paddingX(Unit::medium())
                            ->paddingY(Unit::small())
                            ->rounded(Unit::roundedLg())
                            ->background(Color::slate(50), Pseudo::hover()),
                    ),
                    self::ex('Icon + label button',
                        <<<'CODE'
                        UI::button('')
                            ->borderNone()
                            ->paddingX(Unit::medium())
                            ->paddingY(Unit::xs())
                            ->rounded(Unit::roundedLg())
                            ->background(Color::emerald(500))
                            ->color(Color::white())
                            ->content(
                                UI::row()->gap(Unit::xs())->alignMiddle()->content(
                                    UI::text('✓'),
                                    UI::text('Save'),
                                ),
                            )
                        CODE,
                        fn() => UI::button('')
                            ->borderNone()
                            ->paddingX(Unit::medium())
                            ->paddingY(Unit::xs())
                            ->rounded(Unit::roundedLg())
                            ->background(Color::emerald(500))
                            ->color(Color::white())
                            ->content(
                                UI::row()->gap(Unit::xs())->alignMiddle()->content(
                                    UI::text('✓'),
                                    UI::text('Save'),
                                ),
                            ),
                    ),
                ],
                ['link', 'form'],
            ),

            new ElementDoc(
                'link', 'Link', 'UI::link(string $href, ?string $label = null)', 'Content',
                'Anchor tag — for navigation between pages.',
                'For SPA-style navigation inside a router, prefer `Router::navigate()` on a button. Use Link for external destinations and native browser link semantics.',
                [
                    self::ex('External link',
                        <<<'CODE'
                        UI::link('https://example.com', 'External →')
                            ->color(Color::red(600))
                        CODE,
                        fn() => UI::link('https://example.com', 'External →')
                            ->color(Color::red(600)),
                    ),
                    self::ex('Underlined link with hover color',
                        <<<'CODE'
                        UI::link('#', 'Documentation')
                            ->color(Color::slate(700))
                            ->color(Color::red(600), Pseudo::hover())
                            ->underline()
                        CODE,
                        fn() => UI::link('#', 'Documentation')
                            ->color(Color::slate(700))
                            ->color(Color::red(600), Pseudo::hover())
                            ->underline(),
                    ),
                ],
                ['button'],
            ),

            new ElementDoc(
                'svg', 'Svg', 'UI::svg()', 'Content',
                'Root `<svg>` element for vector graphics. Add shape children via `content()`.',
                'Set the viewport with `viewBox()`, the rendered size with `svgWidth()` / `svgHeight()`, and the default colors with `defaultFill()` / `defaultStroke()`. Shapes are created via static helpers like `Svg::path()`, `Svg::circle()`.',
                [
                    self::ex('A red square icon',
                        <<<'CODE'
                        UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('48')->svgHeight('48')
                            ->content(
                                Svg::rect(2, 2, 20, 20)->fill(Color::red(500)),
                            )
                        CODE,
                        fn() => UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('48')->svgHeight('48')
                            ->content(
                                Svg::rect(2, 2, 20, 20)->fill(Color::red(500)),
                            ),
                    ),
                    self::ex('Outlined circle icon',
                        <<<'CODE'
                        UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('48')->svgHeight('48')
                            ->defaultFill('none')
                            ->defaultStroke(Color::slate(800))
                            ->defaultStrokeWidth('2')
                            ->content(
                                Svg::circle(12, 12, 9),
                                Svg::line(12, 8, 12, 16),
                                Svg::line(8, 12, 16, 12),
                            )
                        CODE,
                        fn() => UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('48')->svgHeight('48')
                            ->defaultFill('none')
                            ->defaultStroke(Color::slate(800))
                            ->defaultStrokeWidth('2')
                            ->content(
                                Svg::circle(12, 12, 9),
                                Svg::line(12, 8, 12, 16),
                                Svg::line(8, 12, 16, 12),
                            ),
                    ),
                ],
                ['svg-path', 'svg-circle', 'svg-rect'],
            ),
        ];
    }

    // ============================================================
    // Forms
    // ============================================================

    /** @return ElementDoc[] */
    private static function forms(): array
    {
        return [
            new ElementDoc(
                'form', 'Form', 'UI::form()', 'Forms',
                'Renders a `<form>`. Wires `onSubmit()` to a typed handler.',
                'Form coordinates a group of inputs. Submit handlers run server-side — read the values straight from your bound state.',
                [
                    self::ex('Form with input + submit',
                        <<<'CODE'
                        UI::form()->content(
                            UI::column()->gap(Unit::small())->content(
                                UI::input()->attr('placeholder', 'Your name'),
                                UI::button('Save'),
                            ),
                        )
                        CODE,
                        fn() => UI::form()->content(
                            UI::column()->gap(Unit::small())->content(
                                UI::input()->attr('placeholder', 'Your name'),
                                UI::button('Save'),
                            ),
                        ),
                    ),
                    self::ex('POST form with multipart enctype',
                        <<<'CODE'
                        UI::form()
                            ->post()
                            ->multipart()
                            ->action('/upload')
                            ->content(
                                UI::column()->gap(Unit::small())->content(
                                    UI::input()->attr('type', 'file'),
                                    UI::button('Upload'),
                                ),
                            )
                        CODE,
                        fn() => UI::form()
                            ->post()
                            ->multipart()
                            ->action('/upload')
                            ->content(
                                UI::column()->gap(Unit::small())->content(
                                    UI::input()->attr('type', 'file'),
                                    UI::button('Upload'),
                                ),
                            ),
                    ),
                ],
                ['input', 'fieldset'],
            ),

            new ElementDoc(
                'input', 'Input', 'UI::input()', 'Forms',
                'A text input. Pair with `useState()` for two-way binding.',
                'Default type is `text`. Use `attr("type", "...")`, `attr("placeholder", "...")`, `onChange()`, `onInput()`. Combine with `useState()` so the server-side property always reflects what the user typed.',
                [
                    self::ex('Text input',
                        <<<'CODE'
                        UI::input()
                            ->attr('placeholder', 'Your name')
                            ->attr('type', 'text')
                            ->paddingX(Unit::small())
                            ->paddingY(Unit::xs())
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->rounded(Unit::roundedLg())
                        CODE,
                        fn() => UI::input()
                            ->attr('placeholder', 'Your name')
                            ->attr('type', 'text')
                            ->paddingX(Unit::small())
                            ->paddingY(Unit::xs())
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->rounded(Unit::roundedLg()),
                    ),
                    self::ex('Email input with focus ring',
                        <<<'CODE'
                        UI::input()
                            ->attr('type', 'email')
                            ->attr('placeholder', 'you@example.com')
                            ->paddingX(Unit::small())
                            ->paddingY(Unit::xs())
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->borderColor(Color::red(500), Pseudo::focus())
                            ->rounded(Unit::roundedLg())
                        CODE,
                        fn() => UI::input()
                            ->attr('type', 'email')
                            ->attr('placeholder', 'you@example.com')
                            ->paddingX(Unit::small())
                            ->paddingY(Unit::xs())
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->borderColor(Color::red(500), Pseudo::focus())
                            ->rounded(Unit::roundedLg()),
                    ),
                    self::ex('Range slider',
                        <<<'CODE'
                        UI::input()
                            ->attr('type', 'range')
                            ->attr('min', '0')
                            ->attr('max', '100')
                            ->attr('value', '50')
                            ->width(Unit::full())
                        CODE,
                        fn() => UI::input()
                            ->attr('type', 'range')
                            ->attr('min', '0')
                            ->attr('max', '100')
                            ->attr('value', '50')
                            ->width(Unit::full()),
                    ),
                ],
                ['textarea', 'select'],
            ),

            new ElementDoc(
                'textarea', 'Textarea', 'UI::textarea()', 'Forms',
                'A multi-line text input.',
                'Use for longer free-text input — comments, bios, descriptions.',
                [
                    self::ex('Multi-line input',
                        <<<'CODE'
                        UI::textarea()
                            ->attr('placeholder', 'Tell us more...')
                            ->attr('rows', '4')
                            ->paddingX(Unit::small())
                            ->paddingY(Unit::xs())
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->rounded(Unit::roundedLg())
                            ->width(Unit::full())
                        CODE,
                        fn() => UI::textarea()
                            ->attr('placeholder', 'Tell us more...')
                            ->attr('rows', '4')
                            ->paddingX(Unit::small())
                            ->paddingY(Unit::xs())
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->rounded(Unit::roundedLg())
                            ->width(Unit::full()),
                    ),
                    self::ex('Pre-filled comment box',
                        <<<'CODE'
                        UI::textarea()
                            ->attr('rows', '3')
                            ->paddingX(Unit::small())
                            ->paddingY(Unit::xs())
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->rounded(Unit::roundedLg())
                            ->width(Unit::full())
                            ->content('Existing comment text.')
                        CODE,
                        fn() => UI::textarea()
                            ->attr('rows', '3')
                            ->paddingX(Unit::small())
                            ->paddingY(Unit::xs())
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->rounded(Unit::roundedLg())
                            ->width(Unit::full())
                            ->content('Existing comment text.'),
                    ),
                ],
                ['input'],
            ),

            new ElementDoc(
                'select', 'Select', 'UI::select()', 'Forms',
                'A dropdown picker. Children must be `Option` or `Optgroup` elements.',
                'Pair with Option children to build a single-choice dropdown. For typed enums, render Options inside a loop over your enum cases.',
                [
                    self::ex('Size picker',
                        <<<'CODE'
                        UI::select()
                            ->paddingX(Unit::small())
                            ->paddingY(Unit::xs())
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->rounded(Unit::roundedLg())
                            ->options(
                                UI::option('Small', 'sm'),
                                UI::option('Medium', 'md'),
                                UI::option('Large', 'lg'),
                            )
                        CODE,
                        fn() => UI::select()
                            ->paddingX(Unit::small())
                            ->paddingY(Unit::xs())
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->rounded(Unit::roundedLg())
                            ->options(
                                UI::option('Small', 'sm'),
                                UI::option('Medium', 'md'),
                                UI::option('Large', 'lg'),
                            ),
                    ),
                    self::ex('Multi-select list',
                        <<<'CODE'
                        UI::select()
                            ->multiple()
                            ->visibleRows(4)
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->rounded(Unit::roundedLg())
                            ->options(
                                UI::option('PHP', 'php'),
                                UI::option('Go', 'go'),
                                UI::option('Rust', 'rs'),
                                UI::option('TypeScript', 'ts'),
                            )
                        CODE,
                        fn() => UI::select()
                            ->multiple()
                            ->visibleRows(4)
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->rounded(Unit::roundedLg())
                            ->options(
                                UI::option('PHP', 'php'),
                                UI::option('Go', 'go'),
                                UI::option('Rust', 'rs'),
                                UI::option('TypeScript', 'ts'),
                            ),
                    ),
                ],
                ['option', 'optgroup'],
            ),

            new ElementDoc(
                'option', 'Option', 'UI::option(string $label, ?string $value = null)', 'Forms',
                'A single `<option>` inside a Select.',
                'The visible label and the underlying value can differ — useful for storing IDs while showing user-friendly names.',
                [
                    self::ex('Option in a Select',
                        <<<'CODE'
                        UI::select()->options(
                            UI::option('United Kingdom', 'gb'),
                            UI::option('Germany', 'de'),
                        )
                        CODE,
                        fn() => UI::select()->options(
                            UI::option('United Kingdom', 'gb'),
                            UI::option('Germany', 'de'),
                        ),
                    ),
                    self::ex('Option matching the label',
                        <<<'CODE'
                        UI::select()->options(
                            UI::option('Apple'),
                            UI::option('Banana'),
                            UI::option('Cherry'),
                        )
                        CODE,
                        fn() => UI::select()->options(
                            UI::option('Apple'),
                            UI::option('Banana'),
                            UI::option('Cherry'),
                        ),
                    ),
                ],
                ['select', 'optgroup'],
            ),

            new ElementDoc(
                'optgroup', 'Optgroup', 'UI::optgroup(string $label)', 'Forms',
                'Groups Options inside a Select under a labelled heading.',
                'Use to organize a long list of options into named sections.',
                [
                    self::ex('Grouped options',
                        <<<'CODE'
                        UI::select()->options(
                            UI::optgroup('Europe')->options(
                                UI::option('France', 'fr'),
                                UI::option('Spain', 'es'),
                            ),
                            UI::optgroup('Africa')->options(
                                UI::option('South Africa', 'za'),
                            ),
                        )
                        CODE,
                        fn() => UI::select()->options(
                            UI::optgroup('Europe')->options(
                                UI::option('France', 'fr'),
                                UI::option('Spain', 'es'),
                            ),
                            UI::optgroup('Africa')->options(
                                UI::option('South Africa', 'za'),
                            ),
                        ),
                    ),
                    self::ex('Disabled group',
                        <<<'CODE'
                        UI::select()->options(
                            UI::optgroup('Available')->options(
                                UI::option('Standard'),
                                UI::option('Pro'),
                            ),
                            UI::optgroup('Coming soon')->disabled()->options(
                                UI::option('Enterprise'),
                            ),
                        )
                        CODE,
                        fn() => UI::select()->options(
                            UI::optgroup('Available')->options(
                                UI::option('Standard'),
                                UI::option('Pro'),
                            ),
                            UI::optgroup('Coming soon')->disabled()->options(
                                UI::option('Enterprise'),
                            ),
                        ),
                    ),
                ],
                ['select', 'option'],
            ),

            new ElementDoc(
                'label', 'Label', 'UI::label(?string $text = null)', 'Forms',
                'A `<label>` — associates a caption with a form control.',
                'Wrap a label around an input or set `for(id)` to target one explicitly. Crucial for accessibility.',
                [
                    self::ex('Labeled input',
                        <<<'CODE'
                        UI::column()->gap(Unit::xs())->content(
                            UI::label('Email')->color(Color::slate(700))->fontSize(FontSize::Small),
                            UI::input()
                                ->attr('type', 'email')
                                ->paddingX(Unit::small())
                                ->paddingY(Unit::xs())
                                ->bordered()
                                ->borderColor(Color::slate(300))
                                ->rounded(Unit::roundedLg()),
                        )
                        CODE,
                        fn() => UI::column()->gap(Unit::xs())->content(
                            UI::label('Email')->color(Color::slate(700))->fontSize(FontSize::Small),
                            UI::input()
                                ->attr('type', 'email')
                                ->paddingX(Unit::small())
                                ->paddingY(Unit::xs())
                                ->bordered()
                                ->borderColor(Color::slate(300))
                                ->rounded(Unit::roundedLg()),
                        ),
                    ),
                    self::ex('Inline checkbox label',
                        <<<'CODE'
                        UI::label()->content(
                            UI::row()->gap(Unit::xs())->alignMiddle()->content(
                                UI::input()->attr('type', 'checkbox'),
                                UI::text('Send me product updates'),
                            ),
                        )
                        CODE,
                        fn() => UI::label()->content(
                            UI::row()->gap(Unit::xs())->alignMiddle()->content(
                                UI::input()->attr('type', 'checkbox'),
                                UI::text('Send me product updates'),
                            ),
                        ),
                    ),
                ],
                ['input', 'fieldset'],
            ),

            new ElementDoc(
                'fieldset', 'Fieldset', 'UI::fieldset()', 'Forms',
                'Groups related form controls.',
                'Pair with a `<legend>` for an accessible heading. Useful for splitting a long form into themed sections.',
                [
                    self::ex('Grouped form fields',
                        <<<'CODE'
                        UI::fieldset()
                            ->padding(Unit::small())
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->rounded(Unit::roundedLg())
                            ->content(
                                UI::column()->gap(Unit::xs())->content(
                                    UI::label('First name'),
                                    UI::input(),
                                ),
                            )
                        CODE,
                        fn() => UI::fieldset()
                            ->padding(Unit::small())
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->rounded(Unit::roundedLg())
                            ->content(
                                UI::column()->gap(Unit::xs())->content(
                                    UI::label('First name'),
                                    UI::input(),
                                ),
                            ),
                    ),
                    self::ex('Legend + disabled fieldset',
                        <<<'CODE'
                        UI::fieldset()
                            ->legend('Account locked')
                            ->disabled()
                            ->padding(Unit::small())
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->rounded(Unit::roundedLg())
                            ->content(
                                UI::column()->gap(Unit::xs())->content(
                                    UI::input()->attr('placeholder', 'Cannot edit'),
                                ),
                            )
                        CODE,
                        fn() => UI::fieldset()
                            ->legend('Account locked')
                            ->disabled()
                            ->padding(Unit::small())
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->rounded(Unit::roundedLg())
                            ->content(
                                UI::column()->gap(Unit::xs())->content(
                                    UI::input()->attr('placeholder', 'Cannot edit'),
                                ),
                            ),
                    ),
                ],
                ['form', 'label'],
            ),

            new ElementDoc(
                'output', 'Output', 'UI::output()', 'Forms',
                'Renders an `<output>` — the result of a form calculation.',
                'Tie to a form for accessible calculated values (e.g. live total beneath a slider).',
                [
                    self::ex('Calculated total',
                        <<<'CODE'
                        UI::output()->content(
                            UI::text('Total: $42.00')->weight(FontWeight::Bold),
                        )
                        CODE,
                        fn() => UI::output()->content(
                            UI::text('Total: $42.00')->weight(FontWeight::Bold),
                        ),
                    ),
                    self::ex('Output beside a slider',
                        <<<'CODE'
                        UI::row()->gap(Unit::small())->alignMiddle()->content(
                            UI::input()
                                ->attr('type', 'range')
                                ->attr('min', '0')
                                ->attr('max', '100')
                                ->attr('value', '60'),
                            UI::output()->content(UI::text('60%')),
                        )
                        CODE,
                        fn() => UI::row()->gap(Unit::small())->alignMiddle()->content(
                            UI::input()
                                ->attr('type', 'range')
                                ->attr('min', '0')
                                ->attr('max', '100')
                                ->attr('value', '60'),
                            UI::output()->content(UI::text('60%')),
                        ),
                    ),
                ],
                ['form'],
            ),

            new ElementDoc(
                'progress', 'Progress', 'UI::progress()', 'Forms',
                'A native `<progress>` bar — shows determinate progress.',
                'Set `value` and `max`. Browser styles vary, but the semantic is consistent.',
                [
                    self::ex('40% progress',
                        <<<'CODE'
                        UI::progress()
                            ->attr('value', '40')
                            ->attr('max', '100')
                            ->width(Unit::full())
                        CODE,
                        fn() => UI::progress()
                            ->attr('value', '40')
                            ->attr('max', '100')
                            ->width(Unit::full()),
                    ),
                    self::ex('Indeterminate progress',
                        <<<'CODE'
                        UI::progress()
                            ->attr('max', '100')
                            ->width(Unit::full())
                        CODE,
                        fn() => UI::progress()
                            ->attr('max', '100')
                            ->width(Unit::full()),
                    ),
                ],
                ['meter'],
            ),

            new ElementDoc(
                'meter', 'Meter', 'UI::meter()', 'Forms',
                'A native `<meter>` — visualizes a value within a known range.',
                'Different from Progress: Meter is for fixed-scale measurements (disk usage, ratings) rather than tasks in flight.',
                [
                    self::ex('7 out of 10',
                        <<<'CODE'
                        UI::meter()
                            ->attr('min', '0')
                            ->attr('max', '10')
                            ->attr('value', '7')
                            ->width(Unit::full())
                        CODE,
                        fn() => UI::meter()
                            ->attr('min', '0')
                            ->attr('max', '10')
                            ->attr('value', '7')
                            ->width(Unit::full()),
                    ),
                    self::ex('Meter with thresholds',
                        <<<'CODE'
                        UI::meter()
                            ->attr('min', '0')
                            ->attr('max', '100')
                            ->attr('low', '30')
                            ->attr('high', '80')
                            ->attr('optimum', '90')
                            ->attr('value', '85')
                            ->width(Unit::full())
                        CODE,
                        fn() => UI::meter()
                            ->attr('min', '0')
                            ->attr('max', '100')
                            ->attr('low', '30')
                            ->attr('high', '80')
                            ->attr('optimum', '90')
                            ->attr('value', '85')
                            ->width(Unit::full()),
                    ),
                ],
                ['progress'],
            ),
        ];
    }

    // ============================================================
    // Lists
    // ============================================================

    /** @return ElementDoc[] */
    private static function lists(): array
    {
        return [
            new ElementDoc(
                'ul', 'Ul', 'UI::ul()', 'Lists',
                'Unordered list — renders `<ul>`.',
                'Wrap a series of `UI::li()` children via `items()`. Mark up bullet lists, nav menus, and any unordered collection.',
                [
                    self::ex('Bullet list',
                        <<<'CODE'
                        UI::ul()->paddingX(Unit::medium())->items(
                            UI::li('Cement'),
                            UI::li('Bricks'),
                            UI::li('Mortar'),
                        )
                        CODE,
                        fn() => UI::ul()->paddingX(Unit::medium())->items(
                            UI::li('Cement'),
                            UI::li('Bricks'),
                            UI::li('Mortar'),
                        ),
                    ),
                    self::ex('Nested sub-list',
                        <<<'CODE'
                        UI::ul()->paddingX(Unit::medium())->items(
                            UI::li('Layout'),
                            UI::li(UI::column()->content(
                                UI::text('Forms'),
                                UI::ul()->paddingX(Unit::medium())->items(
                                    UI::li('Input'),
                                    UI::li('Select'),
                                ),
                            )),
                            UI::li('Media'),
                        )
                        CODE,
                        fn() => UI::ul()->paddingX(Unit::medium())->items(
                            UI::li('Layout'),
                            UI::li(UI::column()->content(
                                UI::text('Forms'),
                                UI::ul()->paddingX(Unit::medium())->items(
                                    UI::li('Input'),
                                    UI::li('Select'),
                                ),
                            )),
                            UI::li('Media'),
                        ),
                    ),
                ],
                ['ol', 'li'],
            ),

            new ElementDoc(
                'ol', 'Ol', 'UI::ol()', 'Lists',
                'Ordered list — renders `<ol>` with numbered children.',
                'Use for sequences and recipes — the browser numbers items automatically. Customize the start number, direction, or marker type with `start()`, `reversed()`, `type()`.',
                [
                    self::ex('Numbered steps',
                        <<<'CODE'
                        UI::ol()->paddingX(Unit::medium())->items(
                            UI::li('Mix mortar'),
                            UI::li('Lay first row'),
                            UI::li('Check level'),
                        )
                        CODE,
                        fn() => UI::ol()->paddingX(Unit::medium())->items(
                            UI::li('Mix mortar'),
                            UI::li('Lay first row'),
                            UI::li('Check level'),
                        ),
                    ),
                    self::ex('Roman numerals starting at 5',
                        <<<'CODE'
                        UI::ol()
                            ->paddingX(Unit::medium())
                            ->type('I')
                            ->start(5)
                            ->items(
                                UI::li('First listed'),
                                UI::li('Second listed'),
                            )
                        CODE,
                        fn() => UI::ol()
                            ->paddingX(Unit::medium())
                            ->type('I')
                            ->start(5)
                            ->items(
                                UI::li('First listed'),
                                UI::li('Second listed'),
                            ),
                    ),
                ],
                ['ul', 'li'],
            ),

            new ElementDoc(
                'li', 'Li', 'UI::li(string|UIElement|null $content)', 'Lists',
                'A single list item. Lives inside Ul or Ol.',
                'Pass a string for the simple case; pass a UIElement for richer item content (e.g. a Row with an icon + label).',
                [
                    self::ex('Plain text item',
                        <<<'CODE'
                        UI::ul()->paddingX(Unit::medium())->items(
                            UI::li('Plain item'),
                        )
                        CODE,
                        fn() => UI::ul()->paddingX(Unit::medium())->items(
                            UI::li('Plain item'),
                        ),
                    ),
                    self::ex('Item with icon + label',
                        <<<'CODE'
                        UI::ul()->paddingX(Unit::medium())->items(
                            UI::li(UI::row()->gap(Unit::xs())->alignMiddle()->content(
                                UI::text('✓')->color(Color::emerald(600)),
                                UI::text('Done item'),
                            )),
                            UI::li(UI::row()->gap(Unit::xs())->alignMiddle()->content(
                                UI::text('✗')->color(Color::red(600)),
                                UI::text('Skipped item'),
                            )),
                        )
                        CODE,
                        fn() => UI::ul()->paddingX(Unit::medium())->items(
                            UI::li(UI::row()->gap(Unit::xs())->alignMiddle()->content(
                                UI::text('✓')->color(Color::emerald(600)),
                                UI::text('Done item'),
                            )),
                            UI::li(UI::row()->gap(Unit::xs())->alignMiddle()->content(
                                UI::text('✗')->color(Color::red(600)),
                                UI::text('Skipped item'),
                            )),
                        ),
                    ),
                ],
                ['ul', 'ol'],
            ),

            new ElementDoc(
                'dt', 'Dt', 'UI::dt(string|UIElement|null $content)', 'Lists',
                'Definition term — the "term" half of a definition list.',
                'Pair with `Dd` inside a container styled as `<dl>`.',
                [
                    self::ex('Term + description',
                        <<<'CODE'
                        UI::column()->gap(Unit::xs())->content(
                            UI::dt('Brick')->weight(FontWeight::Bold),
                            UI::dd('A small, rectangular block of fired clay.')
                                ->color(Color::slate(600))
                                ->paddingLeft(Unit::medium()),
                        )
                        CODE,
                        fn() => UI::column()->gap(Unit::xs())->content(
                            UI::dt('Brick')->weight(FontWeight::Bold),
                            UI::dd('A small, rectangular block of fired clay.')
                                ->color(Color::slate(600))
                                ->paddingLeft(Unit::medium()),
                        ),
                    ),
                    self::ex('Term colored as accent',
                        <<<'CODE'
                        UI::column()->gap(Unit::xs())->content(
                            UI::dt('BrickPHP')
                                ->color(Color::red(600))
                                ->weight(FontWeight::Bold),
                            UI::dd('PHP framework for server-rendered apps.')
                                ->color(Color::slate(700))
                                ->paddingLeft(Unit::medium()),
                        )
                        CODE,
                        fn() => UI::column()->gap(Unit::xs())->content(
                            UI::dt('BrickPHP')
                                ->color(Color::red(600))
                                ->weight(FontWeight::Bold),
                            UI::dd('PHP framework for server-rendered apps.')
                                ->color(Color::slate(700))
                                ->paddingLeft(Unit::medium()),
                        ),
                    ),
                ],
                ['dd'],
            ),

            new ElementDoc(
                'dd', 'Dd', 'UI::dd(string|UIElement|null $content)', 'Lists',
                'Definition description — the "definition" half of a definition list.',
                'Always pair with a preceding Dt.',
                [
                    self::ex('Definition row',
                        <<<'CODE'
                        UI::column()->gap(Unit::xs())->content(
                            UI::dt('Mortar')->weight(FontWeight::Bold),
                            UI::dd('Holds the bricks together.')
                                ->color(Color::slate(600))
                                ->paddingLeft(Unit::medium()),
                        )
                        CODE,
                        fn() => UI::column()->gap(Unit::xs())->content(
                            UI::dt('Mortar')->weight(FontWeight::Bold),
                            UI::dd('Holds the bricks together.')
                                ->color(Color::slate(600))
                                ->paddingLeft(Unit::medium()),
                        ),
                    ),
                    self::ex('Rich description with link',
                        <<<'CODE'
                        UI::column()->gap(Unit::xs())->content(
                            UI::dt('Docs')->weight(FontWeight::Bold),
                            UI::dd()
                                ->paddingLeft(Unit::medium())
                                ->content(
                                    UI::row()->gap(Unit::xs())->content(
                                        UI::text('See'),
                                        UI::link('#', '/api')->color(Color::red(600)),
                                    ),
                                ),
                        )
                        CODE,
                        fn() => UI::column()->gap(Unit::xs())->content(
                            UI::dt('Docs')->weight(FontWeight::Bold),
                            UI::dd()
                                ->paddingLeft(Unit::medium())
                                ->content(
                                    UI::row()->gap(Unit::xs())->content(
                                        UI::text('See'),
                                        UI::link('#', '/api')->color(Color::red(600)),
                                    ),
                                ),
                        ),
                    ),
                ],
                ['dt'],
            ),
        ];
    }

    // ============================================================
    // Tables
    // ============================================================

    /** @return ElementDoc[] */
    private static function tables(): array
    {
        return [
            new ElementDoc(
                'table', 'Table', 'UI::table()', 'Tables',
                'Renders a `<table>`. Add rows via `UI::tableRow()`.',
                'Use for actual tabular data — not for layout.',
                [
                    self::ex('Two-row table',
                        <<<'CODE'
                        UI::table()->content(
                            UI::tableRow(
                                new \BrickPHP\UI\TableHeading('Name'),
                                new \BrickPHP\UI\TableHeading('Role'),
                            ),
                            UI::tableRow(
                                new \BrickPHP\UI\TableCell('Ada'),
                                new \BrickPHP\UI\TableCell('Engineer'),
                            ),
                        )
                        CODE,
                        fn() => UI::table()->content(
                            UI::tableRow(
                                new \BrickPHP\UI\TableHeading('Name'),
                                new \BrickPHP\UI\TableHeading('Role'),
                            ),
                            UI::tableRow(
                                new \BrickPHP\UI\TableCell('Ada'),
                                new \BrickPHP\UI\TableCell('Engineer'),
                            ),
                        ),
                    ),
                    self::ex('Striped table with multiple rows',
                        <<<'CODE'
                        UI::table()
                            ->width(Unit::full())
                            ->content(
                                UI::tableRow(
                                    new \BrickPHP\UI\TableHeading('Product'),
                                    new \BrickPHP\UI\TableHeading('Stock'),
                                ),
                                UI::tableRow(
                                    new \BrickPHP\UI\TableCell('Bricks'),
                                    new \BrickPHP\UI\TableCell('1240'),
                                ),
                                UI::tableRow(
                                    new \BrickPHP\UI\TableCell('Mortar'),
                                    new \BrickPHP\UI\TableCell('48'),
                                ),
                                UI::tableRow(
                                    new \BrickPHP\UI\TableCell('Cement'),
                                    new \BrickPHP\UI\TableCell('210'),
                                ),
                            )
                        CODE,
                        fn() => UI::table()
                            ->width(Unit::full())
                            ->content(
                                UI::tableRow(
                                    new \BrickPHP\UI\TableHeading('Product'),
                                    new \BrickPHP\UI\TableHeading('Stock'),
                                ),
                                UI::tableRow(
                                    new \BrickPHP\UI\TableCell('Bricks'),
                                    new \BrickPHP\UI\TableCell('1240'),
                                ),
                                UI::tableRow(
                                    new \BrickPHP\UI\TableCell('Mortar'),
                                    new \BrickPHP\UI\TableCell('48'),
                                ),
                                UI::tableRow(
                                    new \BrickPHP\UI\TableCell('Cement'),
                                    new \BrickPHP\UI\TableCell('210'),
                                ),
                            ),
                    ),
                ],
                ['table-row'],
            ),

            new ElementDoc(
                'table-row', 'TableRow', 'UI::tableRow(...$cells)', 'Tables',
                'A single `<tr>` — pass TableCell or TableHeading children.',
                'Variadic constructor — the cells are positional.',
                [
                    self::ex('Row of cells',
                        <<<'CODE'
                        UI::table()->content(
                            UI::tableRow(
                                new \BrickPHP\UI\TableCell('Row data'),
                                new \BrickPHP\UI\TableCell('More data'),
                            ),
                        )
                        CODE,
                        fn() => UI::table()->content(
                            UI::tableRow(
                                new \BrickPHP\UI\TableCell('Row data'),
                                new \BrickPHP\UI\TableCell('More data'),
                            ),
                        ),
                    ),
                    self::ex('Heading row + body row',
                        <<<'CODE'
                        UI::table()->content(
                            UI::tableRow(
                                new \BrickPHP\UI\TableHeading('Col A'),
                                new \BrickPHP\UI\TableHeading('Col B'),
                            ),
                            UI::tableRow(
                                new \BrickPHP\UI\TableCell('1'),
                                new \BrickPHP\UI\TableCell('2'),
                            ),
                        )
                        CODE,
                        fn() => UI::table()->content(
                            UI::tableRow(
                                new \BrickPHP\UI\TableHeading('Col A'),
                                new \BrickPHP\UI\TableHeading('Col B'),
                            ),
                            UI::tableRow(
                                new \BrickPHP\UI\TableCell('1'),
                                new \BrickPHP\UI\TableCell('2'),
                            ),
                        ),
                    ),
                ],
                ['table'],
            ),
        ];
    }

    // ============================================================
    // Media
    // ============================================================

    /** @return ElementDoc[] */
    private static function media(): array
    {
        return [
            new ElementDoc(
                'video', 'Video', 'UI::video()', 'Media',
                'Renders a `<video>` player.',
                'Add `controls()`, `autoplay()`, `loop()`, `muted()`. Attach format alternatives via `sources()` and captions via `tracks()`.',
                [
                    self::ex('Video with controls',
                        <<<'CODE'
                        UI::video()
                            ->controls()
                            ->attr('width', '320')
                            ->poster('https://placehold.co/320x180/0f172a/fff?text=Video')
                        CODE,
                        fn() => UI::video()
                            ->controls()
                            ->attr('width', '320')
                            ->poster('https://placehold.co/320x180/0f172a/fff?text=Video'),
                    ),
                    self::ex('Autoplay muted background video',
                        <<<'CODE'
                        UI::video()
                            ->autoplay()
                            ->loop()
                            ->muted()
                            ->playsinline()
                            ->attr('width', '320')
                            ->poster('https://placehold.co/320x180/E11D48/fff?text=Looping')
                        CODE,
                        fn() => UI::video()
                            ->autoplay()
                            ->loop()
                            ->muted()
                            ->playsinline()
                            ->attr('width', '320')
                            ->poster('https://placehold.co/320x180/E11D48/fff?text=Looping'),
                    ),
                ],
                ['audio', 'source'],
            ),

            new ElementDoc(
                'audio', 'Audio', 'UI::audio()', 'Media',
                'Renders an `<audio>` player.',
                'Same shape as Video — supply Source children via `sources()`.',
                [
                    self::ex('Audio player with controls',
                        <<<'CODE'
                        UI::audio()->controls()
                        CODE,
                        fn() => UI::audio()->controls(),
                    ),
                    self::ex('Looping muted audio',
                        <<<'CODE'
                        UI::audio()
                            ->controls()
                            ->loop()
                            ->muted()
                            ->preload('metadata')
                        CODE,
                        fn() => UI::audio()
                            ->controls()
                            ->loop()
                            ->muted()
                            ->preload('metadata'),
                    ),
                ],
                ['video', 'source'],
            ),

            new ElementDoc(
                'source', 'Source', 'UI::source(string $src)', 'Media',
                'A media source — for Video, Audio, or Picture.',
                'Lets the browser pick the best format / quality given what it supports.',
                [
                    self::ex('Single source on a video',
                        <<<'CODE'
                        UI::video()
                            ->controls()
                            ->attr('width', '320')
                            ->sources(
                                UI::source('/clip.webm')->type('video/webm'),
                            )
                        CODE,
                        fn() => UI::video()
                            ->controls()
                            ->attr('width', '320')
                            ->sources(
                                UI::source('/clip.webm')->type('video/webm'),
                            ),
                    ),
                    self::ex('Multiple formats with fallback',
                        <<<'CODE'
                        UI::video()
                            ->controls()
                            ->attr('width', '320')
                            ->sources(
                                UI::source('/clip.av1.mp4')->type('video/mp4; codecs=av01.0'),
                                UI::source('/clip.webm')->type('video/webm'),
                                UI::source('/clip.mp4')->type('video/mp4'),
                            )
                        CODE,
                        fn() => UI::video()
                            ->controls()
                            ->attr('width', '320')
                            ->sources(
                                UI::source('/clip.av1.mp4')->type('video/mp4; codecs=av01.0'),
                                UI::source('/clip.webm')->type('video/webm'),
                                UI::source('/clip.mp4')->type('video/mp4'),
                            ),
                    ),
                ],
                ['video', 'audio', 'picture'],
            ),

            new ElementDoc(
                'track', 'Track', 'UI::track(string $src)', 'Media',
                'A subtitle / caption track for Video or Audio.',
                'Pass a WebVTT file via `$src`. Use `captions()`, `subtitles()`, `chapters()`, or `descriptions()` to set the track kind.',
                [
                    self::ex('English captions',
                        <<<'CODE'
                        UI::video()
                            ->controls()
                            ->attr('width', '320')
                            ->tracks(
                                UI::track('/captions.vtt')
                                    ->captions()
                                    ->srclang('en')
                                    ->label('English'),
                            )
                        CODE,
                        fn() => UI::video()
                            ->controls()
                            ->attr('width', '320')
                            ->tracks(
                                UI::track('/captions.vtt')
                                    ->captions()
                                    ->srclang('en')
                                    ->label('English'),
                            ),
                    ),
                    self::ex('Subtitles default-on',
                        <<<'CODE'
                        UI::video()
                            ->controls()
                            ->attr('width', '320')
                            ->tracks(
                                UI::track('/subs-fr.vtt')
                                    ->subtitles()
                                    ->srclang('fr')
                                    ->label('Français')
                                    ->default(),
                            )
                        CODE,
                        fn() => UI::video()
                            ->controls()
                            ->attr('width', '320')
                            ->tracks(
                                UI::track('/subs-fr.vtt')
                                    ->subtitles()
                                    ->srclang('fr')
                                    ->label('Français')
                                    ->default(),
                            ),
                    ),
                ],
                ['video'],
            ),

            new ElementDoc(
                'iframe', 'Iframe', 'UI::iframe()', 'Media',
                'Embeds another document via `<iframe>`.',
                'Set the URL with `attr("src", "...")`. Always restrict with `sandbox` when embedding untrusted content.',
                [
                    self::ex('Sandboxed embed',
                        <<<'CODE'
                        UI::iframe()
                            ->attr('src', 'about:blank')
                            ->attr('sandbox', '')
                            ->width(Unit::full())
                            ->height(Unit::px(120))
                            ->bordered()
                            ->borderColor(Color::slate(200))
                        CODE,
                        fn() => UI::iframe()
                            ->attr('src', 'about:blank')
                            ->attr('sandbox', '')
                            ->width(Unit::full())
                            ->height(Unit::px(120))
                            ->bordered()
                            ->borderColor(Color::slate(200)),
                    ),
                    self::ex('Lazy-loaded iframe',
                        <<<'CODE'
                        UI::iframe()
                            ->attr('src', 'about:blank')
                            ->attr('loading', 'lazy')
                            ->attr('title', 'Map')
                            ->width(Unit::full())
                            ->height(Unit::px(160))
                            ->rounded(Unit::roundedLg())
                        CODE,
                        fn() => UI::iframe()
                            ->attr('src', 'about:blank')
                            ->attr('loading', 'lazy')
                            ->attr('title', 'Map')
                            ->width(Unit::full())
                            ->height(Unit::px(160))
                            ->rounded(Unit::roundedLg()),
                    ),
                ],
                ['video'],
            ),

            new ElementDoc(
                'canvas', 'Canvas', 'UI::canvas()', 'Media',
                'A drawable `<canvas>` surface.',
                'For pixel-level drawing — charts, games, animations. Pair with `attr("id", ...)` and a Custom Event handler that triggers a client-side draw routine.',
                [
                    self::ex('Empty canvas',
                        <<<'CODE'
                        UI::canvas()
                            ->attr('width', '320')
                            ->attr('height', '120')
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->background(Color::slate(50))
                        CODE,
                        fn() => UI::canvas()
                            ->attr('width', '320')
                            ->attr('height', '120')
                            ->bordered()
                            ->borderColor(Color::slate(300))
                            ->background(Color::slate(50)),
                    ),
                    self::ex('Square canvas with id hook',
                        <<<'CODE'
                        UI::canvas()
                            ->attr('id', 'chart')
                            ->attr('width', '160')
                            ->attr('height', '160')
                            ->bordered()
                            ->borderColor(Color::slate(300))
                        CODE,
                        fn() => UI::canvas()
                            ->attr('id', 'chart')
                            ->attr('width', '160')
                            ->attr('height', '160')
                            ->bordered()
                            ->borderColor(Color::slate(300)),
                    ),
                ],
                ['svg'],
            ),

            new ElementDoc(
                'picture', 'Picture', 'UI::picture()', 'Media',
                'Responsive image container — `<picture>` with Source children.',
                'Lets the browser pick the best image based on viewport size and format support. Fallback `<img>` is your final Image child.',
                [
                    self::ex('Picture with fallback img',
                        <<<'CODE'
                        UI::picture()->fallback(
                            UI::image(
                                'https://placehold.co/200x120/0f172a/fff?text=Picture',
                                'Demo',
                            )->width(Unit::px(200)),
                        )
                        CODE,
                        fn() => UI::picture()->fallback(
                            UI::image(
                                'https://placehold.co/200x120/0f172a/fff?text=Picture',
                                'Demo',
                            )->width(Unit::px(200)),
                        ),
                    ),
                    self::ex('Multiple sources by format',
                        <<<'CODE'
                        UI::picture()
                            ->sources(
                                UI::source('/hero.avif')->type('image/avif'),
                                UI::source('/hero.webp')->type('image/webp'),
                            )
                            ->fallback(
                                UI::image(
                                    'https://placehold.co/200x120/E11D48/fff?text=Hero',
                                    'Hero',
                                )->width(Unit::px(200)),
                            )
                        CODE,
                        fn() => UI::picture()
                            ->sources(
                                UI::source('/hero.avif')->type('image/avif'),
                                UI::source('/hero.webp')->type('image/webp'),
                            )
                            ->fallback(
                                UI::image(
                                    'https://placehold.co/200x120/E11D48/fff?text=Hero',
                                    'Hero',
                                )->width(Unit::px(200)),
                            ),
                    ),
                ],
                ['image', 'source'],
            ),
        ];
    }

    // ============================================================
    // Inline / text
    // ============================================================

    /** @return ElementDoc[] */
    private static function inlineText(): array
    {
        return [
            new ElementDoc(
                'code', 'Code', 'UI::code(string $content = "")', 'Inline & Text',
                'Renders a `<code>` block — for inline code or programming text.',
                'For multi-line snippets, wrap inside a `Pre`.',
                [
                    self::ex('Inline code chip',
                        <<<'CODE'
                        UI::row()->gap(Unit::xs())->alignMiddle()->content(
                            UI::text('Run'),
                            UI::code('docker compose up')
                                ->background(Color::slate(100))
                                ->paddingX(Unit::xs())
                                ->rounded(Unit::roundedSm()),
                        )
                        CODE,
                        fn() => UI::row()->gap(Unit::xs())->alignMiddle()->content(
                            UI::text('Run'),
                            UI::code('docker compose up')
                                ->background(Color::slate(100))
                                ->paddingX(Unit::xs())
                                ->rounded(Unit::roundedSm()),
                        ),
                    ),
                    self::ex('Accent-colored code',
                        <<<'CODE'
                        UI::code('UI::column()')
                            ->color(Color::red(700))
                            ->background(Color::red(50))
                            ->paddingX(Unit::xs())
                            ->rounded(Unit::roundedSm())
                        CODE,
                        fn() => UI::code('UI::column()')
                            ->color(Color::red(700))
                            ->background(Color::red(50))
                            ->paddingX(Unit::xs())
                            ->rounded(Unit::roundedSm()),
                    ),
                ],
                ['pre'],
            ),

            new ElementDoc(
                'pre', 'Pre', 'UI::pre(?string $content = null)', 'Inline & Text',
                'Preformatted text — preserves whitespace and line breaks.',
                'Combine with Code for code blocks, or use directly for any text that should keep its formatting.',
                [
                    self::ex('Dark code block',
                        <<<'CODE'
                        UI::pre()
                            ->padding(Unit::small())
                            ->background(Color::slate(900))
                            ->color(Color::white())
                            ->rounded(Unit::roundedLg())
                            ->content(
                                UI::code("function hello() {\n  return 'world';\n}"),
                            )
                        CODE,
                        fn() => UI::pre()
                            ->padding(Unit::small())
                            ->background(Color::slate(900))
                            ->color(Color::white())
                            ->rounded(Unit::roundedLg())
                            ->content(
                                UI::code("function hello() {\n  return 'world';\n}"),
                            ),
                    ),
                    self::ex('Light terminal-style',
                        <<<'CODE'
                        UI::pre()
                            ->padding(Unit::small())
                            ->background(Color::slate(50))
                            ->color(Color::slate(900))
                            ->bordered()
                            ->borderColor(Color::slate(200))
                            ->rounded(Unit::roundedLg())
                            ->content(
                                UI::code("$ docker compose up\nServer ready on :8000"),
                            )
                        CODE,
                        fn() => UI::pre()
                            ->padding(Unit::small())
                            ->background(Color::slate(50))
                            ->color(Color::slate(900))
                            ->bordered()
                            ->borderColor(Color::slate(200))
                            ->rounded(Unit::roundedLg())
                            ->content(
                                UI::code("$ docker compose up\nServer ready on :8000"),
                            ),
                    ),
                ],
                ['code'],
            ),

            new ElementDoc(
                'blockquote', 'Blockquote', 'UI::blockquote(?string $content = null)', 'Inline & Text',
                'Renders a `<blockquote>` for extended quotations.',
                'Style with a left border and italic text for the classic quote-block look.',
                [
                    self::ex('Simple quote',
                        <<<'CODE'
                        UI::blockquote('Less is more.')
                            ->paddingX(Unit::medium())
                            ->paddingY(Unit::xs())
                            ->borderLeft(4)
                            ->borderColor(Color::slate(400))
                            ->color(Color::slate(700))
                        CODE,
                        fn() => UI::blockquote('Less is more.')
                            ->paddingX(Unit::medium())
                            ->paddingY(Unit::xs())
                            ->borderLeft(4)
                            ->borderColor(Color::slate(400))
                            ->color(Color::slate(700)),
                    ),
                    self::ex('Accented pullquote with citation',
                        <<<'CODE'
                        UI::blockquote('A brick a day keeps the bug away.')
                            ->cite('BrickPHP Manual')
                            ->fontSize(FontSize::Large)
                            ->paddingX(Unit::medium())
                            ->paddingY(Unit::small())
                            ->borderLeft(4)
                            ->borderColor(Color::red(500))
                            ->background(Color::red(50))
                            ->color(Color::slate(800))
                        CODE,
                        fn() => UI::blockquote('A brick a day keeps the bug away.')
                            ->cite('BrickPHP Manual')
                            ->fontSize(FontSize::Large)
                            ->paddingX(Unit::medium())
                            ->paddingY(Unit::small())
                            ->borderLeft(4)
                            ->borderColor(Color::red(500))
                            ->background(Color::red(50))
                            ->color(Color::slate(800)),
                    ),
                ],
                ['paragraph'],
            ),
        ];
    }

    // ============================================================
    // Misc
    // ============================================================

    /** @return ElementDoc[] */
    private static function misc(): array
    {
        return [
            new ElementDoc(
                'br', 'Br', 'UI::br()', 'Misc',
                'Renders a line break — `<br>`.',
                'Hard line break inside flowing text. Prefer block elements (Column, Paragraph) for layout — Br is for prose.',
                [
                    self::ex('Line break in a paragraph',
                        <<<'CODE'
                        UI::paragraph('First line.')->content(
                            UI::br(),
                            UI::text('Second line.'),
                        )
                        CODE,
                        fn() => UI::paragraph('First line.')->content(
                            UI::br(),
                            UI::text('Second line.'),
                        ),
                    ),
                    self::ex('Multi-line address',
                        <<<'CODE'
                        UI::text('123 Brick Lane')->content(
                            UI::br(),
                            UI::text('Springfield, USA'),
                            UI::br(),
                            UI::text('90210'),
                        )
                        CODE,
                        fn() => UI::text('123 Brick Lane')->content(
                            UI::br(),
                            UI::text('Springfield, USA'),
                            UI::br(),
                            UI::text('90210'),
                        ),
                    ),
                ],
                ['hr'],
            ),

            new ElementDoc(
                'hr', 'Hr', 'UI::hr()', 'Misc',
                'Renders a horizontal rule — `<hr>`.',
                'Semantic divider between thematic sections.',
                [
                    self::ex('Default thin rule',
                        <<<'CODE'
                        UI::column()->content(
                            UI::text('Above'),
                            UI::hr()->borderColor(Color::slate(300)),
                            UI::text('Below'),
                        )
                        CODE,
                        fn() => UI::column()->content(
                            UI::text('Above'),
                            UI::hr()->borderColor(Color::slate(300)),
                            UI::text('Below'),
                        ),
                    ),
                    self::ex('Thick accent rule',
                        <<<'CODE'
                        UI::column()->gap(Unit::small())->content(
                            UI::text('Section A'),
                            UI::hr()
                                ->bordered(2)
                                ->borderColor(Color::red(500)),
                            UI::text('Section B'),
                        )
                        CODE,
                        fn() => UI::column()->gap(Unit::small())->content(
                            UI::text('Section A'),
                            UI::hr()
                                ->bordered(2)
                                ->borderColor(Color::red(500)),
                            UI::text('Section B'),
                        ),
                    ),
                ],
                ['divider', 'br'],
            ),

            new ElementDoc(
                'span', 'Span', 'UI::span(string|UIElement|null $content)', 'Misc',
                'Generic inline container — `<span>`.',
                'Inline counterpart to Div. Use to style a fragment of inline content without breaking the line.',
                [
                    self::ex('Highlighted phrase',
                        <<<'CODE'
                        UI::paragraph('This is a ')->content(
                            UI::span('highlighted')
                                ->background(Color::yellow(200))
                                ->paddingX(Unit::xs()),
                            UI::text(' word.'),
                        )
                        CODE,
                        fn() => UI::paragraph('This is a ')->content(
                            UI::span('highlighted')
                                ->background(Color::yellow(200))
                                ->paddingX(Unit::xs()),
                            UI::text(' word.'),
                        ),
                    ),
                    self::ex('Inline accent label',
                        <<<'CODE'
                        UI::paragraph('Built with ')->content(
                            UI::span('BrickPHP')
                                ->color(Color::red(600))
                                ->weight(FontWeight::SemiBold),
                            UI::text(' — by you.'),
                        )
                        CODE,
                        fn() => UI::paragraph('Built with ')->content(
                            UI::span('BrickPHP')
                                ->color(Color::red(600))
                                ->weight(FontWeight::SemiBold),
                            UI::text(' — by you.'),
                        ),
                    ),
                ],
                ['div', 'text'],
            ),

            new ElementDoc(
                'div', 'Div', 'UI::div()', 'Misc',
                'Generic block container — `<div>` with no semantic meaning.',
                'Reach for Container instead in most cases — Div is here mainly when you want the exact `<div>` tag for CSS or testing reasons.',
                [
                    self::ex('Plain block',
                        <<<'CODE'
                        UI::div()
                            ->padding(Unit::small())
                            ->background(Color::slate(100))
                            ->content(UI::text('Block.'))
                        CODE,
                        fn() => UI::div()
                            ->padding(Unit::small())
                            ->background(Color::slate(100))
                            ->content(UI::text('Block.')),
                    ),
                    self::ex('Coloured stripe',
                        <<<'CODE'
                        UI::div()
                            ->height(Unit::px(6))
                            ->width(Unit::px(120))
                            ->background(Color::red(500))
                            ->rounded(Unit::roundedFull())
                        CODE,
                        fn() => UI::div()
                            ->height(Unit::px(6))
                            ->width(Unit::px(120))
                            ->background(Color::red(500))
                            ->rounded(Unit::roundedFull()),
                    ),
                ],
                ['container', 'span'],
            ),
        ];
    }

    // ============================================================
    // Presets / utilities
    // ============================================================

    /** @return ElementDoc[] */
    private static function presets(): array
    {
        return [
            new ElementDoc(
                'spacer', 'Spacer', 'UI::spacer()', 'Presets',
                'A flexible spacer — expands to consume available space inside a Row or Column.',
                'Drop one between two children to push them to opposite ends — equivalent to `flex: 1 1 auto`.',
                [
                    self::ex('Push to opposite ends',
                        <<<'CODE'
                        UI::row()
                            ->width(Unit::full())
                            ->alignMiddle()
                            ->content(
                                UI::text('Left'),
                                UI::spacer(),
                                UI::text('Right'),
                            )
                        CODE,
                        fn() => UI::row()
                            ->width(Unit::full())
                            ->alignMiddle()
                            ->content(
                                UI::text('Left'),
                                UI::spacer(),
                                UI::text('Right'),
                            ),
                    ),
                    self::ex('Vertical spacer in a column',
                        <<<'CODE'
                        UI::column()
                            ->height(Unit::px(120))
                            ->padding(Unit::small())
                            ->background(Color::slate(100))
                            ->content(
                                UI::text('Top item'),
                                UI::spacer(),
                                UI::text('Bottom item'),
                            )
                        CODE,
                        fn() => UI::column()
                            ->height(Unit::px(120))
                            ->padding(Unit::small())
                            ->background(Color::slate(100))
                            ->content(
                                UI::text('Top item'),
                                UI::spacer(),
                                UI::text('Bottom item'),
                            ),
                    ),
                ],
                ['row'],
            ),

            new ElementDoc(
                'divider', 'Divider', 'UI::divider()', 'Presets',
                'A thin horizontal line — pre-styled for sectioning content.',
                'Less work than configuring Hr by hand; tweak via `background()` or wrap in a Container with margins.',
                [
                    self::ex('Default divider',
                        <<<'CODE'
                        UI::column()->gap(Unit::small())->content(
                            UI::text('Section A'),
                            UI::divider(),
                            UI::text('Section B'),
                        )
                        CODE,
                        fn() => UI::column()->gap(Unit::small())->content(
                            UI::text('Section A'),
                            UI::divider(),
                            UI::text('Section B'),
                        ),
                    ),
                    self::ex('Colored divider',
                        <<<'CODE'
                        UI::column()->gap(Unit::small())->content(
                            UI::text('Divider in brand colour'),
                            UI::divider()->background(Color::red(400)),
                            UI::text('Below'),
                        )
                        CODE,
                        fn() => UI::column()->gap(Unit::small())->content(
                            UI::text('Divider in brand colour'),
                            UI::divider()->background(Color::red(400)),
                            UI::text('Below'),
                        ),
                    ),
                ],
                ['hr'],
            ),

            new ElementDoc(
                'card', 'Card', 'UI::card()', 'Presets',
                'A pre-styled white container with rounded corners, shadow, and padding.',
                'The fastest path to a clean card look — start here and override what you need (background, padding, shadow).',
                [
                    self::ex('Simple card',
                        <<<'CODE'
                        UI::card()->content(
                            UI::text('Title')->weight(FontWeight::Bold),
                            UI::text('Card body.')
                                ->color(Color::slate(600))
                                ->fontSize(FontSize::Small),
                        )
                        CODE,
                        fn() => UI::card()->content(
                            UI::text('Title')->weight(FontWeight::Bold),
                            UI::text('Card body.')
                                ->color(Color::slate(600))
                                ->fontSize(FontSize::Small),
                        ),
                    ),
                    self::ex('Card with accent stripe + footer',
                        <<<'CODE'
                        UI::card()
                            ->borderTop(3)
                            ->borderColor(Color::red(500))
                            ->maxWidth(Unit::px(260))
                            ->content(
                                UI::text('Premium plan')->weight(FontWeight::Bold),
                                UI::text('$19 / month')
                                    ->fontSize(FontSize::TwoXL)
                                    ->paddingY(Unit::xs()),
                                UI::button('Subscribe')
                                    ->background(Color::red(500))
                                    ->color(Color::white())
                                    ->paddingX(Unit::medium())
                                    ->paddingY(Unit::xs())
                                    ->rounded(Unit::roundedLg())
                                    ->borderNone(),
                            )
                        CODE,
                        fn() => UI::card()
                            ->borderTop(3)
                            ->borderColor(Color::red(500))
                            ->maxWidth(Unit::px(260))
                            ->content(
                                UI::text('Premium plan')->weight(FontWeight::Bold),
                                UI::text('$19 / month')
                                    ->fontSize(FontSize::TwoXL)
                                    ->paddingY(Unit::xs()),
                                UI::button('Subscribe')
                                    ->background(Color::red(500))
                                    ->color(Color::white())
                                    ->paddingX(Unit::medium())
                                    ->paddingY(Unit::xs())
                                    ->rounded(Unit::roundedLg())
                                    ->borderNone(),
                            ),
                    ),
                ],
                ['container'],
            ),

            new ElementDoc(
                'badge', 'Badge', 'UI::badge(string $text)', 'Presets',
                'A small, rounded, gray pill — for tags and meta labels.',
                'Compact text label with built-in padding and rounding. Override `background()` / `color()` for status variants.',
                [
                    self::ex('Status variants',
                        <<<'CODE'
                        UI::row()->gap(Unit::xs())->content(
                            UI::badge('Default'),
                            UI::badge('Beta')
                                ->background(Color::amber(100))
                                ->color(Color::amber(800)),
                            UI::badge('Live')
                                ->background(Color::emerald(100))
                                ->color(Color::emerald(800)),
                            UI::badge('Error')
                                ->background(Color::red(100))
                                ->color(Color::red(800)),
                        )
                        CODE,
                        fn() => UI::row()->gap(Unit::xs())->content(
                            UI::badge('Default'),
                            UI::badge('Beta')
                                ->background(Color::amber(100))
                                ->color(Color::amber(800)),
                            UI::badge('Live')
                                ->background(Color::emerald(100))
                                ->color(Color::emerald(800)),
                            UI::badge('Error')
                                ->background(Color::red(100))
                                ->color(Color::red(800)),
                        ),
                    ),
                    self::ex('Numeric counter badge',
                        <<<'CODE'
                        UI::row()->gap(Unit::xs())->alignMiddle()->content(
                            UI::text('Inbox'),
                            UI::badge('12')
                                ->background(Color::red(500))
                                ->color(Color::white()),
                        )
                        CODE,
                        fn() => UI::row()->gap(Unit::xs())->alignMiddle()->content(
                            UI::text('Inbox'),
                            UI::badge('12')
                                ->background(Color::red(500))
                                ->color(Color::white()),
                        ),
                    ),
                ],
                ['pill'],
            ),

            new ElementDoc(
                'pill', 'Pill', 'UI::pill(string $text)', 'Presets',
                'A larger badge with primary-color styling.',
                'For CTAs masquerading as text labels — "new feature" indicators, tags with more weight than a Badge.',
                [
                    self::ex('Primary pill',
                        <<<'CODE'
                        UI::pill('Premium')
                        CODE,
                        fn() => UI::pill('Premium'),
                    ),
                    self::ex('Recolored pill',
                        <<<'CODE'
                        UI::pill('Sold out')
                            ->background(Color::slate(700))
                        CODE,
                        fn() => UI::pill('Sold out')
                            ->background(Color::slate(700)),
                    ),
                ],
                ['badge'],
            ),

            new ElementDoc(
                'avatar', 'Avatar', 'UI::avatar(string $src, string $alt = "")', 'Presets',
                'A round, cover-cropped image — for user avatars.',
                'Wraps Image with `roundedFull()` and `objectCover()`. Add `width()` and `height()` for sizing.',
                [
                    self::ex('Round avatar',
                        <<<'CODE'
                        UI::avatar('https://placehold.co/80x80/E11D48/fff?text=Me', 'Me')
                            ->width(Unit::px(64))
                            ->height(Unit::px(64))
                        CODE,
                        fn() => UI::avatar('https://placehold.co/80x80/E11D48/fff?text=Me', 'Me')
                            ->width(Unit::px(64))
                            ->height(Unit::px(64)),
                    ),
                    self::ex('Avatar stack',
                        <<<'CODE'
                        UI::row()->content(
                            UI::avatar('https://placehold.co/64x64/E11D48/fff?text=A', 'A')
                                ->width(Unit::px(40))
                                ->height(Unit::px(40))
                                ->bordered(2)
                                ->borderColor(Color::white()),
                            UI::avatar('https://placehold.co/64x64/0EA5E9/fff?text=B', 'B')
                                ->width(Unit::px(40))
                                ->height(Unit::px(40))
                                ->bordered(2)
                                ->borderColor(Color::white())
                                ->marginLeft(Unit::px(-12)),
                            UI::avatar('https://placehold.co/64x64/10B981/fff?text=C', 'C')
                                ->width(Unit::px(40))
                                ->height(Unit::px(40))
                                ->bordered(2)
                                ->borderColor(Color::white())
                                ->marginLeft(Unit::px(-12)),
                        )
                        CODE,
                        fn() => UI::row()->content(
                            UI::avatar('https://placehold.co/64x64/E11D48/fff?text=A', 'A')
                                ->width(Unit::px(40))
                                ->height(Unit::px(40))
                                ->bordered(2)
                                ->borderColor(Color::white()),
                            UI::avatar('https://placehold.co/64x64/0EA5E9/fff?text=B', 'B')
                                ->width(Unit::px(40))
                                ->height(Unit::px(40))
                                ->bordered(2)
                                ->borderColor(Color::white())
                                ->marginLeft(Unit::px(-12)),
                            UI::avatar('https://placehold.co/64x64/10B981/fff?text=C', 'C')
                                ->width(Unit::px(40))
                                ->height(Unit::px(40))
                                ->bordered(2)
                                ->borderColor(Color::white())
                                ->marginLeft(Unit::px(-12)),
                        ),
                    ),
                ],
                ['image'],
            ),

            new ElementDoc(
                'center', 'Center', 'UI::center()', 'Presets',
                'A Row that centers its children both axes.',
                'Shorthand for `UI::row()->center()` — useful for splash screens and any "one thing dead center" layout.',
                [
                    self::ex('Centered text',
                        <<<'CODE'
                        UI::center()
                            ->minHeight(Unit::px(120))
                            ->background(Color::slate(100))
                            ->content(UI::text('Centered'))
                        CODE,
                        fn() => UI::center()
                            ->minHeight(Unit::px(120))
                            ->background(Color::slate(100))
                            ->content(UI::text('Centered')),
                    ),
                    self::ex('Centered CTA',
                        <<<'CODE'
                        UI::center()
                            ->minHeight(Unit::px(160))
                            ->background(Color::red(50))
                            ->content(
                                UI::column()->gap(Unit::small())->alignCenter()->content(
                                    UI::text('Ready to build?')->weight(FontWeight::Bold),
                                    UI::button('Get started')
                                        ->background(Color::red(500))
                                        ->color(Color::white())
                                        ->paddingX(Unit::medium())
                                        ->paddingY(Unit::xs())
                                        ->rounded(Unit::roundedLg())
                                        ->borderNone(),
                                ),
                            )
                        CODE,
                        fn() => UI::center()
                            ->minHeight(Unit::px(160))
                            ->background(Color::red(50))
                            ->content(
                                UI::column()->gap(Unit::small())->alignCenter()->content(
                                    UI::text('Ready to build?')->weight(FontWeight::Bold),
                                    UI::button('Get started')
                                        ->background(Color::red(500))
                                        ->color(Color::white())
                                        ->paddingX(Unit::medium())
                                        ->paddingY(Unit::xs())
                                        ->rounded(Unit::roundedLg())
                                        ->borderNone(),
                                ),
                            ),
                    ),
                ],
                ['row'],
            ),
        ];
    }

    // ============================================================
    // SVG shapes
    // ============================================================

    /** @return ElementDoc[] */
    private static function svg(): array
    {
        return [
            new ElementDoc(
                'svg-path', 'SvgPath', 'Svg::path(string $d)', 'SVG Shapes',
                'A `<path>` — the most flexible SVG shape; draws via path commands.',
                'The `$d` string follows the SVG path mini-language ("M x y", "L x y", "C ...", "Z"). For complex shapes, design in a vector editor and copy the path out.',
                [
                    self::ex('Diamond shape',
                        <<<'CODE'
                        UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('48')->svgHeight('48')
                            ->content(
                                Svg::path('M12 2L22 12 12 22 2 12Z')->fill(Color::red(500)),
                            )
                        CODE,
                        fn() => UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('48')->svgHeight('48')
                            ->content(
                                Svg::path('M12 2L22 12 12 22 2 12Z')->fill(Color::red(500)),
                            ),
                    ),
                    self::ex('Outlined chevron',
                        <<<'CODE'
                        UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('48')->svgHeight('48')
                            ->content(
                                Svg::path('M8 6l8 6-8 6')
                                    ->fill('none')
                                    ->stroke(Color::slate(700))
                                    ->strokeWidth('2')
                                    ->strokeLinecap('round')
                                    ->strokeLinejoin('round'),
                            )
                        CODE,
                        fn() => UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('48')->svgHeight('48')
                            ->content(
                                Svg::path('M8 6l8 6-8 6')
                                    ->fill('none')
                                    ->stroke(Color::slate(700))
                                    ->strokeWidth('2')
                                    ->strokeLinecap('round')
                                    ->strokeLinejoin('round'),
                            ),
                    ),
                ],
                ['svg', 'svg-polygon'],
            ),

            new ElementDoc(
                'svg-circle', 'SvgCircle', 'Svg::circle(float $cx, float $cy, float $r)', 'SVG Shapes',
                'A `<circle>` — center + radius.',
                'Use for dots, indicators, simple bullets, decorative shapes.',
                [
                    self::ex('Filled red dot',
                        <<<'CODE'
                        UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('48')->svgHeight('48')
                            ->content(
                                Svg::circle(12, 12, 10)->fill(Color::red(500)),
                            )
                        CODE,
                        fn() => UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('48')->svgHeight('48')
                            ->content(
                                Svg::circle(12, 12, 10)->fill(Color::red(500)),
                            ),
                    ),
                    self::ex('Stroked ring',
                        <<<'CODE'
                        UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('48')->svgHeight('48')
                            ->content(
                                Svg::circle(12, 12, 9)
                                    ->fill('none')
                                    ->stroke(Color::indigo(500))
                                    ->strokeWidth('3'),
                            )
                        CODE,
                        fn() => UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('48')->svgHeight('48')
                            ->content(
                                Svg::circle(12, 12, 9)
                                    ->fill('none')
                                    ->stroke(Color::indigo(500))
                                    ->strokeWidth('3'),
                            ),
                    ),
                ],
                ['svg-ellipse', 'svg'],
            ),

            new ElementDoc(
                'svg-rect', 'SvgRect', 'Svg::rect(float $x, float $y, float $w, float $h)', 'SVG Shapes',
                'A `<rect>` — axis-aligned rectangle.',
                'Use for bars in a bar chart, brick-style logos (hello!), and any rectangular SVG region.',
                [
                    self::ex('Filled square',
                        <<<'CODE'
                        UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('48')->svgHeight('48')
                            ->content(
                                Svg::rect(2, 2, 20, 20)->fill(Color::slate(800)),
                            )
                        CODE,
                        fn() => UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('48')->svgHeight('48')
                            ->content(
                                Svg::rect(2, 2, 20, 20)->fill(Color::slate(800)),
                            ),
                    ),
                    self::ex('Stacked bars (mini chart)',
                        <<<'CODE'
                        UI::svg()
                            ->viewBox(0, 0, 60, 30)
                            ->svgWidth('120')->svgHeight('60')
                            ->content(
                                Svg::rect(2,  18, 8, 10)->fill(Color::red(500)),
                                Svg::rect(14, 12, 8, 16)->fill(Color::red(500)),
                                Svg::rect(26, 6,  8, 22)->fill(Color::red(500)),
                                Svg::rect(38, 14, 8, 14)->fill(Color::red(500)),
                                Svg::rect(50, 10, 8, 18)->fill(Color::red(500)),
                            )
                        CODE,
                        fn() => UI::svg()
                            ->viewBox(0, 0, 60, 30)
                            ->svgWidth('120')->svgHeight('60')
                            ->content(
                                Svg::rect(2,  18, 8, 10)->fill(Color::red(500)),
                                Svg::rect(14, 12, 8, 16)->fill(Color::red(500)),
                                Svg::rect(26, 6,  8, 22)->fill(Color::red(500)),
                                Svg::rect(38, 14, 8, 14)->fill(Color::red(500)),
                                Svg::rect(50, 10, 8, 18)->fill(Color::red(500)),
                            ),
                    ),
                ],
                ['svg'],
            ),

            new ElementDoc(
                'svg-line', 'SvgLine', 'Svg::line(float $x1, float $y1, float $x2, float $y2)', 'SVG Shapes',
                'A `<line>` — straight segment between two points.',
                'Set color and thickness with `stroke()` and `strokeWidth()`.',
                [
                    self::ex('Diagonal line',
                        <<<'CODE'
                        UI::svg()
                            ->viewBox(0, 0, 100, 100)
                            ->svgWidth('100')->svgHeight('100')
                            ->content(
                                Svg::line(0, 0, 100, 100)
                                    ->stroke(Color::slate(700))
                                    ->strokeWidth('4'),
                            )
                        CODE,
                        fn() => UI::svg()
                            ->viewBox(0, 0, 100, 100)
                            ->svgWidth('100')->svgHeight('100')
                            ->content(
                                Svg::line(0, 0, 100, 100)
                                    ->stroke(Color::slate(700))
                                    ->strokeWidth('4'),
                            ),
                    ),
                    self::ex('Dashed cross-hairs',
                        <<<'CODE'
                        UI::svg()
                            ->viewBox(0, 0, 100, 100)
                            ->svgWidth('120')->svgHeight('120')
                            ->content(
                                Svg::line(50, 0, 50, 100)
                                    ->stroke(Color::red(500))
                                    ->strokeWidth('2')
                                    ->strokeLinecap('round'),
                                Svg::line(0, 50, 100, 50)
                                    ->stroke(Color::red(500))
                                    ->strokeWidth('2')
                                    ->strokeLinecap('round'),
                            )
                        CODE,
                        fn() => UI::svg()
                            ->viewBox(0, 0, 100, 100)
                            ->svgWidth('120')->svgHeight('120')
                            ->content(
                                Svg::line(50, 0, 50, 100)
                                    ->stroke(Color::red(500))
                                    ->strokeWidth('2')
                                    ->strokeLinecap('round'),
                                Svg::line(0, 50, 100, 50)
                                    ->stroke(Color::red(500))
                                    ->strokeWidth('2')
                                    ->strokeLinecap('round'),
                            ),
                    ),
                ],
                ['svg-polyline'],
            ),

            new ElementDoc(
                'svg-polygon', 'SvgPolygon', 'Svg::polygon(string $points)', 'SVG Shapes',
                'A closed `<polygon>` — points separated by spaces.',
                'Each point is "x,y". The polygon is closed automatically.',
                [
                    self::ex('Triangle',
                        <<<'CODE'
                        UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('48')->svgHeight('48')
                            ->content(
                                Svg::polygon('12,2 22,22 2,22')->fill(Color::amber(500)),
                            )
                        CODE,
                        fn() => UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('48')->svgHeight('48')
                            ->content(
                                Svg::polygon('12,2 22,22 2,22')->fill(Color::amber(500)),
                            ),
                    ),
                    self::ex('Hexagon outline',
                        <<<'CODE'
                        UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('48')->svgHeight('48')
                            ->content(
                                Svg::polygon('12,2 22,7 22,17 12,22 2,17 2,7')
                                    ->fill('none')
                                    ->stroke(Color::indigo(600))
                                    ->strokeWidth('2'),
                            )
                        CODE,
                        fn() => UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('48')->svgHeight('48')
                            ->content(
                                Svg::polygon('12,2 22,7 22,17 12,22 2,17 2,7')
                                    ->fill('none')
                                    ->stroke(Color::indigo(600))
                                    ->strokeWidth('2'),
                            ),
                    ),
                ],
                ['svg-polyline'],
            ),

            new ElementDoc(
                'svg-polyline', 'SvgPolyline', 'Svg::polyline(string $points)', 'SVG Shapes',
                'An open `<polyline>` — like Polygon but not closed.',
                'For sparklines and connected paths that shouldn\'t fill.',
                [
                    self::ex('Sparkline',
                        <<<'CODE'
                        UI::svg()
                            ->viewBox(0, 0, 40, 20)
                            ->svgWidth('120')->svgHeight('60')
                            ->content(
                                Svg::polyline('0,18 10,8 20,12 30,4 40,10')
                                    ->fill('none')
                                    ->stroke(Color::emerald(500))
                                    ->strokeWidth('2'),
                            )
                        CODE,
                        fn() => UI::svg()
                            ->viewBox(0, 0, 40, 20)
                            ->svgWidth('120')->svgHeight('60')
                            ->content(
                                Svg::polyline('0,18 10,8 20,12 30,4 40,10')
                                    ->fill('none')
                                    ->stroke(Color::emerald(500))
                                    ->strokeWidth('2'),
                            ),
                    ),
                    self::ex('Rounded zig-zag',
                        <<<'CODE'
                        UI::svg()
                            ->viewBox(0, 0, 40, 20)
                            ->svgWidth('120')->svgHeight('60')
                            ->content(
                                Svg::polyline('0,15 8,5 16,15 24,5 32,15 40,5')
                                    ->fill('none')
                                    ->stroke(Color::red(500))
                                    ->strokeWidth('2')
                                    ->strokeLinejoin('round')
                                    ->strokeLinecap('round'),
                            )
                        CODE,
                        fn() => UI::svg()
                            ->viewBox(0, 0, 40, 20)
                            ->svgWidth('120')->svgHeight('60')
                            ->content(
                                Svg::polyline('0,15 8,5 16,15 24,5 32,15 40,5')
                                    ->fill('none')
                                    ->stroke(Color::red(500))
                                    ->strokeWidth('2')
                                    ->strokeLinejoin('round')
                                    ->strokeLinecap('round'),
                            ),
                    ),
                ],
                ['svg-line', 'svg-polygon'],
            ),

            new ElementDoc(
                'svg-ellipse', 'SvgEllipse', 'Svg::ellipse(float $cx, float $cy, float $rx, float $ry)', 'SVG Shapes',
                'A `<ellipse>` — circle with independent x/y radii.',
                'Use when an oval shape matters; otherwise prefer Circle.',
                [
                    self::ex('Filled oval',
                        <<<'CODE'
                        UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('60')->svgHeight('48')
                            ->content(
                                Svg::ellipse(12, 12, 10, 6)->fill(Color::cyan(500)),
                            )
                        CODE,
                        fn() => UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('60')->svgHeight('48')
                            ->content(
                                Svg::ellipse(12, 12, 10, 6)->fill(Color::cyan(500)),
                            ),
                    ),
                    self::ex('Stroked stadium shape',
                        <<<'CODE'
                        UI::svg()
                            ->viewBox(0, 0, 60, 24)
                            ->svgWidth('120')->svgHeight('48')
                            ->content(
                                Svg::ellipse(30, 12, 26, 10)
                                    ->fill('none')
                                    ->stroke(Color::slate(700))
                                    ->strokeWidth('2'),
                            )
                        CODE,
                        fn() => UI::svg()
                            ->viewBox(0, 0, 60, 24)
                            ->svgWidth('120')->svgHeight('48')
                            ->content(
                                Svg::ellipse(30, 12, 26, 10)
                                    ->fill('none')
                                    ->stroke(Color::slate(700))
                                    ->strokeWidth('2'),
                            ),
                    ),
                ],
                ['svg-circle'],
            ),

            new ElementDoc(
                'svg-group', 'SvgGroup', 'Svg::g()', 'SVG Shapes',
                'A `<g>` — groups SVG children so a transform / opacity applies to all of them.',
                'Reach for Group when you want to translate, rotate, or scale several shapes as a unit.',
                [
                    self::ex('Rotated square via group',
                        <<<'CODE'
                        UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('48')->svgHeight('48')
                            ->content(
                                Svg::g()
                                    ->transform('rotate(45 12 12)')
                                    ->content(
                                        Svg::rect(6, 6, 12, 12)->fill(Color::red(500)),
                                    ),
                            )
                        CODE,
                        fn() => UI::svg()
                            ->viewBox(0, 0, 24, 24)
                            ->svgWidth('48')->svgHeight('48')
                            ->content(
                                Svg::g()
                                    ->transform('rotate(45 12 12)')
                                    ->content(
                                        Svg::rect(6, 6, 12, 12)->fill(Color::red(500)),
                                    ),
                            ),
                    ),
                    self::ex('Translated + half-opacity overlay',
                        <<<'CODE'
                        UI::svg()
                            ->viewBox(0, 0, 32, 32)
                            ->svgWidth('64')->svgHeight('64')
                            ->content(
                                Svg::rect(2, 2, 20, 20)->fill(Color::indigo(500)),
                                Svg::g()
                                    ->transform('translate(10 10)')
                                    ->opacity('0.6')
                                    ->content(
                                        Svg::rect(0, 0, 20, 20)->fill(Color::red(500)),
                                    ),
                            )
                        CODE,
                        fn() => UI::svg()
                            ->viewBox(0, 0, 32, 32)
                            ->svgWidth('64')->svgHeight('64')
                            ->content(
                                Svg::rect(2, 2, 20, 20)->fill(Color::indigo(500)),
                                Svg::g()
                                    ->transform('translate(10 10)')
                                    ->opacity('0.6')
                                    ->content(
                                        Svg::rect(0, 0, 20, 20)->fill(Color::red(500)),
                                    ),
                            ),
                    ),
                ],
                ['svg'],
            ),
        ];
    }
}
