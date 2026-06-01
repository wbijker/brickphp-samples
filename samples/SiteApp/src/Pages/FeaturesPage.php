<?php

namespace Samples\SiteApp\Pages;

use Samples\SiteApp\Components\CodeBlock;
use Samples\SiteApp\Components\SectionHeading;
use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;

class FeaturesPage extends Component
{
    protected function build(): VNode
    {
        return UI::column()
            ->maxWidth(Unit::px(900))
            ->marginX(Unit::auto())
            ->padding(Unit::extraLarge())
            ->gap(Unit::extraLarge())
            ->content(
                new SectionHeading(
                    'Framework Features',
                    'Everything you need to build web apps — all in PHP.'
                ),
                $this->serverSideSection(),
                $this->uiDesignSection(),
                $this->stateSection(),
                $this->routingSection(),
                $this->patchingSection(),
            );
    }

    // ── 1. All Server-Side PHP ──

    private function serverSideSection(): VNode
    {
        return UI::column()
            ->gap(Unit::large())
            ->content(
                $this->featureHeader('All Server-Side PHP', 'No frontend/backend boundary. One language, one runtime.'),
                UI::grid(2)
                    ->gap(Unit::medium())
                    ->content(
                        $this->featureItem(
                            'Components',
                            'Extend Component, implement build(). State lives on the server.',
                            <<<'PHP'
class ProfileCard extends Component
{
    public function __construct(
        private string $name,
        private string $role,
    ) {}

    protected function build(): VNode
    {
        return UI::column()
            ->padding(Unit::large())
            ->content(
                UI::text($this->name)
                    ->weight(FontWeight::Bold),
                UI::text($this->role)
                    ->color(Color::slate(500)),
            );
    }
}
PHP
                        ),
                        $this->featureItem(
                            'Event Handlers',
                            'Closures run on the server. The client sends the event, server processes it.',
                            <<<'PHP'
UI::button('Delete')
    ->danger()
    ->onClick(function () {
        // This runs on the PHP server
        $this->items = array_filter(
            $this->items,
            fn($item) => $item['id'] !== $this->selectedId
        );
    })
PHP
                        ),
                        $this->featureItem(
                            'Server State',
                            'Session, localStorage, sessionStorage, cookies — all managed from PHP.',
                            <<<'PHP'
// State is automatically persisted via session
class Cart extends Component
{
    private array $items = [];
    private float $total = 0.0;

    protected function initialize(): void
    {
        $this->useState($this->items);
        $this->useState($this->total);
    }
}
PHP
                        ),
                        $this->featureItem(
                            'Database Queries',
                            'Query your database directly in components. No API layer needed.',
                            <<<'PHP'
protected function build(): VNode
{
    // Direct database access from components
    $users = $db->query(
        "SELECT * FROM users WHERE active = 1"
    );

    return UI::column()->content(
        ...array_map(
            fn($u) => new UserRow($u),
            $users
        )
    );
}
PHP
                        ),
                    ),
                // Full feature list
                $this->serverFeatureList(),
            );
    }

    private function serverFeatureList(): VNode
    {
        $features = [
            'Components' => 'Stateful, composable UI building blocks with lifecycle hooks',
            'Event Handling' => 'Click, change, input — all events processed server-side via closures',
            'State Management' => 'Session, localStorage, sessionStorage, cookies — from PHP',
            'Routing' => 'Client-side navigation with server-rendered routes and URL params',
            'DOM Patching' => 'Virtual DOM diffing on server, minimal patches sent to client',
            'CSS Generation' => 'Styles computed from declarative API, compressed and cached',
            'Conditional Rendering' => 'shouldRender() to skip components, array spreading for lists',
            'Component Communication' => 'Props down, callbacks up — closures bind parent state',
            'List Rendering' => 'array_map with key() for efficient list diffing',
            'Form Handling' => 'Input binding via on("change") with server-side validation',
            'File Uploads' => 'Standard PHP $_FILES — no special client code needed',
            'Authentication' => 'Session-based auth, middleware — standard PHP patterns',
            'Database Access' => 'PDO, ORMs, or CodeQuery — query directly from components',
            'Caching' => 'APCu, Redis, file cache — standard PHP caching strategies',
            'Email' => 'mail(), PHPMailer, or any PHP mailer — no API boundary',
            'PDF Generation' => 'DomPDF, TCPDF — generate and serve from components',
            'Image Processing' => 'GD, Imagick — process uploads server-side',
            'Cron Jobs' => 'Standard PHP scheduled tasks, same codebase',
            'WebSockets' => 'Ratchet or Swoole for real-time — server-side push',
            'API Endpoints' => 'Same PHP app can serve JSON alongside UI responses',
        ];

        $items = [];
        foreach ($features as $name => $desc) {
            $items[] = UI::row()
                ->gap(Unit::small())
                ->alignTop()
                ->paddingY(Unit::xs())
                ->content(
                    UI::text('•')
                        ->color(Color::indigo(500))
                        ->weight(FontWeight::Bold)
                        ->noShrink(),
                    UI::column()->content(
                        UI::text($name)
                            ->weight(FontWeight::SemiBold)
                            ->fontSize(FontSize::Small)
                            ->color(Color::slate(700)),
                        UI::text($desc)
                            ->fontSize(FontSize::ExtraSmall)
                            ->color(Color::slate(500)),
                    ),
                );
        }

        return UI::column()
            ->background(Color::white())
            ->padding(Unit::large())
            ->rounded(Unit::roundedXl())
            ->shadow(Shadow::Small)
            ->gap(Unit::xs())
            ->content(
                UI::text('Complete Server-Side Feature List')
                    ->weight(FontWeight::SemiBold)
                    ->fontSize(FontSize::Large)
                    ->color(Color::slate(800))
                    ->paddingBottom(Unit::small()),
                ...$items
            );
    }

    // ── 2. UI Design — Abstracted from CSS ──

    private function uiDesignSection(): VNode
    {
        return UI::column()
            ->gap(Unit::large())
            ->content(
                $this->featureHeader(
                    'UI Design — Abstracted from CSS',
                    'Build GUIs by what they do, not how to style them. CSS is generated.'
                ),
                UI::grid(2)
                    ->gap(Unit::medium())
                    ->content(
                        $this->featureItem(
                            'Semantic Layout',
                            'column(), row(), grid() — describe structure, not display:flex.',
                            <<<'PHP'
// What you write:
UI::row()
    ->alignMiddle()
    ->alignBetween()
    ->gap(Unit::medium())

// What gets generated:
// display: flex;
// flex-direction: row;
// align-items: center;
// justify-content: space-between;
// gap: 1rem;
PHP
                        ),
                        $this->featureItem(
                            'Responsive Design',
                            'Breakpoint modifiers on any value — no media query boilerplate.',
                            <<<'PHP'
UI::column()->content(
    UI::text('Responsive Title')
        ->fontSize(FontSize::Large)
        // Values with breakpoints — pass a Pseudo for each variant
        ->width(Unit::full())                       // mobile: 100%
        ->width(Unit::half(), Pseudo::md())         // tablet: 50%
        ->width(Unit::third(), Pseudo::lg())        // desktop: 33%
        ->padding(Unit::small())
        ->padding(Unit::large(), Pseudo::md())
)
PHP
                        ),
                        $this->featureItem(
                            'Color System',
                            'Named palettes with shades. Hover, dark mode, focus — all declarative.',
                            <<<'PHP'
UI::button('Save')
    ->background(Color::indigo(500))
    ->background(Color::indigo(600), Pseudo::hover())
    ->background(Color::indigo(400), Pseudo::active())
    ->background(Color::indigo(700), Pseudo::dark())
    ->color(Color::white())
    ->borderColor(Color::indigo(300))
    ->borderColor(Color::indigo(400), Pseudo::focus())
PHP
                        ),
                        $this->featureItem(
                            'Sizing with Units',
                            'Semantic sizes, percentages, viewport units — no magic numbers.',
                            <<<'PHP'
UI::column()
    ->width(Unit::full())         // 100%
    ->maxWidth(Unit::px(900))     // 900px
    ->padding(Unit::large())      // 1.5rem
    ->gap(Unit::medium())         // 1rem
    ->minHeight(Unit::screen())   // 100vh
    ->marginX(Unit::auto())

// Fractional:
->width(Unit::half())            // 50%
->width(Unit::third())           // 33.33%
->width(Unit::twoThirds())      // 66.67%
PHP
                        ),
                    ),
                $this->cssComparisonTable(),
            );
    }

    private function cssComparisonTable(): VNode
    {
        $comparisons = [
            ['UI::column()', 'display: flex; flex-direction: column;'],
            ['UI::row()->alignBetween()', 'display: flex; justify-content: space-between;'],
            ['->rounded(Unit::roundedLg())', 'border-radius: 0.5rem;'],
            ['->shadow(Shadow::Large)', 'box-shadow: 0 10px 15px -3px rgba(0,0,0,.1)...'],
            ['->fontSize(FontSize::TwoXL)', 'font-size: 1.5rem; line-height: 2rem;'],
            ['->weight(FontWeight::Bold)', 'font-weight: 700;'],
            ['->background(Color::indigo(500))', 'background-color: #6366f1;'],
            ['->padding(Unit::large())', 'padding: 1.5rem;'],
            ['->gap(Unit::medium())', 'gap: 1rem;'],
            ['->extend()', 'width: 100%; height: 100%;'],
            ['->clickable()', 'cursor: pointer;'],
            ['->clipContent()', 'overflow: hidden;'],
            ['->truncate()', 'overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'],
            ['->grow()', 'flex-grow: 1;'],
            ['->width(Unit::half(), Pseudo::md())', '@media (min-width: 768px) { width: 50% }'],
        ];

        $rows = [];
        foreach ($comparisons as $i => [$php, $css]) {
            $bg = $i % 2 === 0 ? Color::slate(50) : Color::white();
            $rows[] = UI::row()
                ->background($bg)
                ->content(
                    UI::text($php)
                        ->mono()
                        ->fontSize(FontSize::ExtraSmall)
                        ->color(Color::indigo(600))
                        ->padding(Unit::small())
                        ->width(Unit::half()),
                    UI::text($css)
                        ->mono()
                        ->fontSize(FontSize::ExtraSmall)
                        ->color(Color::slate(500))
                        ->padding(Unit::small())
                        ->width(Unit::half()),
                );
        }

        return UI::column()
            ->background(Color::white())
            ->rounded(Unit::roundedXl())
            ->shadow(Shadow::Small)
            ->clipContent()
            ->content(
                UI::row()
                    ->background(Color::slate(100))
                    ->content(
                        UI::text('Brick PHP')
                            ->weight(FontWeight::SemiBold)
                            ->fontSize(FontSize::Small)
                            ->color(Color::slate(700))
                            ->padding(Unit::small())
                            ->width(Unit::half()),
                        UI::text('Generated CSS')
                            ->weight(FontWeight::SemiBold)
                            ->fontSize(FontSize::Small)
                            ->color(Color::slate(700))
                            ->padding(Unit::small())
                            ->width(Unit::half()),
                    ),
                ...$rows
            );
    }

    // ── 3. State Management ──

    private function stateSection(): VNode
    {
        return UI::column()
            ->gap(Unit::large())
            ->content(
                $this->featureHeader('State Management', 'Multiple storage backends, all controlled from PHP.'),
                new CodeBlock(<<<'PHP'
// State persists across interactions automatically
class ShoppingCart extends Component
{
    private array $items = [];
    private string $promoCode = '';

    protected function initialize(): void
    {
        // Server session (default) — survives page reload
        $this->useState($this->items);

        // Or use client-side storage:
        // $this->useState($this->items, StateManagers::$localStorage);
        // $this->useState($this->items, StateManagers::$sessionStorage);
        // $this->useState($this->items, StateManagers::$cookie);
    }

    protected function build(): VNode
    {
        return UI::column()->content(
            UI::text('Cart (' . count($this->items) . ' items)')
                ->weight(FontWeight::Bold),

            ...array_map(fn($item) =>
                UI::row()->alignBetween()->content(
                    UI::text($item['name']),
                    UI::button('Remove')
                        ->ghost()
                        ->onClick(fn() => $this->removeItem($item['id']))
                ),
                $this->items
            ),

            UI::input()
                ->placeholder('Promo code')
                ->value($this->promoCode)
                ->onChange(fn(InputEvent $e) => $this->promoCode = $e->value ?? ''),
        );
    }
}
PHP
                ),
            );
    }

    // ── 4. Routing ──

    private function routingSection(): VNode
    {
        return UI::column()
            ->gap(Unit::large())
            ->content(
                $this->featureHeader('Routing', 'Client-side navigation, server-rendered pages.'),
                new CodeBlock(<<<'PHP'
// Define routes with typed parameters
class AppRoutes
{
    public static RoutePath $product;

    public static function init(): void
    {
        self::$product = new RoutePath(
            "/products/{category}/{id}",
            queryParams: ["sort", "limit"],
            class: ProductRoute::class
        );
    }
}

// Route params are typed PHP objects
class ProductRoute
{
    public function __construct(
        public string $category = '',
        public int $id = 0,
        public string $sort = 'name',
        public int $limit = 20,
    ) {}
}

// Use in a Router component
protected function build(): VNode
{
    return new Router(routes: [
        new Route(
            path: '/about',
            component: new AboutPage()
        ),
        new Route(
            path: AppRoutes::$product,
            component: fn(ProductRoute $r) => new ProductPage($r)
        ),
    ], fallback: new NotFoundPage());
}

// Generate URLs from typed objects
$url = AppRoutes::$product->toUrl(
    new ProductRoute(category: 'electronics', id: 42)
);
// → /products/electronics/42
PHP
                ),
            );
    }

    // ── 5. DOM Patching ──

    private function patchingSection(): VNode
    {
        return UI::column()
            ->gap(Unit::large())
            ->content(
                $this->featureHeader('DOM Patching', 'Server-side virtual DOM diffing. Only changes are sent to the client.'),
                new CodeBlock(<<<'PHP'
// The framework handles this automatically:
//
// 1. User clicks a button
//    → Client sends: {event: "click", path: "0,2,1", value: null}
//
// 2. Server re-renders the old tree (before event)
//    $oldUi = $app->render($state, null, RenderPhase::Initial);
//
// 3. Finds the node, executes the event handler
//    $node = $oldUi->findByPath($path);
//    $node->executeEvent($event, $state, $value);
//
// 4. Saves state, renders new tree
//    $app->finalize($state);
//    $newUi = $app->render($state, null, RenderPhase::Patch);
//
// 5. Diffs old vs new, generates minimal patches
//    $patcher = new Patcher();
//    $newUi->compare($oldUi, $patcher);
//
// 6. Client receives JSON patches and applies them:
//    {patches: [
//      {op: "replace_text", path: "0,1", value: "Count: 5"},
//      {op: "set_attribute", path: "0,2", name: "class", value: "..."}
//    ]}
//
// Result: only the changed DOM nodes are updated.
// No full page reload. No client-side framework.
PHP,
                    'Architecture'
                ),
            );
    }

    // ── Helpers ──

    private function featureHeader(string $title, string $subtitle): VNode
    {
        return UI::column()
            ->gap(Unit::xs())
            ->paddingBottom(Unit::small())
            ->borderBottom()
            ->borderColor(Color::slate(200))
            ->content(
                UI::text($title)
                    ->fontSize(FontSize::TwoXL)
                    ->weight(FontWeight::Bold)
                    ->color(Color::indigo(700)),
                UI::text($subtitle)
                    ->fontSize(FontSize::Base)
                    ->color(Color::slate(500)),
            );
    }

    private function featureItem(string $title, string $description, string $code): VNode
    {
        return UI::column()
            ->background(Color::white())
            ->padding(Unit::large())
            ->rounded(Unit::roundedXl())
            ->shadow(Shadow::Small)
            ->gap(Unit::small())
            ->content(
                UI::text($title)
                    ->weight(FontWeight::SemiBold)
                    ->color(Color::slate(800)),
                UI::text($description)
                    ->fontSize(FontSize::Small)
                    ->color(Color::slate(500)),
                new CodeBlock($code),
            );
    }
}
