<?php

namespace Samples\SiteApp\Pages;

use Samples\SiteApp\Components\DemoCard;
use Samples\SiteApp\Components\SectionHeading;
use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;

class ComponentsPage extends Component
{
    private int $counter = 0;
    private string $selectedTab = 'layout';

    protected function initialize(): void
    {
        $this->useState($this->counter);
        $this->useState($this->selectedTab);
    }

    protected function build(): VNode
    {
        return UI::column()
            ->maxWidth(Unit::px(900))
            ->marginX(Unit::auto())
            ->padding(Unit::extraLarge())
            ->gap(Unit::extraLarge())
            ->content(
                new SectionHeading(
                    'Live Component Demos',
                    'Interactive examples — every click is processed by PHP on the server.'
                ),
                $this->buildTabBar(),
                $this->buildActiveTab(),
            );
    }

    private function buildTabBar(): VNode
    {
        $tabs = [
            'layout' => 'Layout',
            'content' => 'Content',
            'interactive' => 'Interactive',
            'patterns' => 'Patterns',
        ];

        $items = [];
        foreach ($tabs as $key => $label) {
            $isActive = $this->selectedTab === $key;
            $items[] = UI::button($label)
                ->borderNone()
                ->background($isActive ? Color::indigo(500) : Color::white())
                ->color($isActive ? Color::white() : Color::slate(600))
                ->padding(Unit::small())
                ->paddingX(Unit::medium())
                ->rounded(Unit::roundedLg())
                ->weight($isActive ? FontWeight::SemiBold : FontWeight::Normal)
                ->shadow($isActive ? Shadow::Small : Shadow::Medium)
                ->clickable()
                ->onClick(fn() => $this->selectedTab = $key);
        }

        return UI::row()
            ->gap(Unit::small())
            ->wrap()
            ->content(...$items);
    }

    private function buildActiveTab(): VNode
    {
        return match ($this->selectedTab) {
            'content' => $this->contentTab(),
            'interactive' => $this->interactiveTab(),
            'patterns' => $this->patternsTab(),
            default => $this->layoutTab(),
        };
    }

    // ── Layout Tab ──

    private function layoutTab(): VNode
    {
        return UI::column()
            ->gap(Unit::large())
            ->content(
                new DemoCard(
                    title: 'Row & Column',
                    description: 'Flexbox layouts with semantic alignment methods.',
                    demo: UI::column()->gap(Unit::medium())->content(
                        UI::row()
                            ->gap(Unit::small())
                            ->alignMiddle()
                            ->content(
                                $this->colorBox('1', Color::blue(400)),
                                $this->colorBox('2', Color::blue(500)),
                                $this->colorBox('3', Color::blue(600)),
                            ),
                        UI::row()
                            ->gap(Unit::small())
                            ->alignBetween()
                            ->content(
                                $this->colorBox('A', Color::emerald(400)),
                                $this->colorBox('B', Color::emerald(500)),
                                $this->colorBox('C', Color::emerald(600)),
                            ),
                    ),
                    code: <<<'PHP'
// Horizontal with centered items
UI::row()
    ->gap(Unit::small())
    ->alignMiddle()
    ->content($a, $b, $c)

// Spread items evenly
UI::row()
    ->alignBetween()
    ->content($a, $b, $c)
PHP
                ),

                new DemoCard(
                    title: 'Grid Layout',
                    description: 'CSS Grid with column count — no grid-template boilerplate.',
                    demo: UI::grid(3)
                        ->gap(Unit::small())
                        ->content(
                            ...array_map(
                                fn($i) => $this->colorBox((string)$i, Color::violet(300 + $i * 50)),
                                range(1, 6)
                            )
                        ),
                    code: <<<'PHP'
UI::grid(3)
    ->gap(Unit::small())
    ->content($item1, $item2, ...$items)
PHP
                ),

                new DemoCard(
                    title: 'Card with Shadow',
                    description: 'Rounded containers with shadows — common UI patterns as methods.',
                    demo: UI::row()->gap(Unit::medium())->content(
                        UI::column()
                            ->background(Color::white())
                            ->padding(Unit::medium())
                            ->rounded(Unit::roundedLg())
                            ->shadow(Shadow::Small)
                            ->content(UI::text('Small shadow')->fontSize(FontSize::Small)),
                        UI::column()
                            ->background(Color::white())
                            ->padding(Unit::medium())
                            ->rounded(Unit::roundedLg())
                            ->shadow(Shadow::Medium)
                            ->content(UI::text('Medium shadow')->fontSize(FontSize::Small)),
                        UI::column()
                            ->background(Color::white())
                            ->padding(Unit::medium())
                            ->rounded(Unit::roundedLg())
                            ->shadow(Shadow::Large)
                            ->content(UI::text('Large shadow')->fontSize(FontSize::Small)),
                    ),
                    code: <<<'PHP'
UI::column()
    ->background(Color::white())
    ->padding(Unit::medium())
    ->rounded(Unit::roundedLg())
    ->shadow(Shadow::Large)
    ->content(...)
PHP
                ),
            );
    }

    // ── Content Tab ──

    private function contentTab(): VNode
    {
        return UI::column()
            ->gap(Unit::large())
            ->content(
                new DemoCard(
                    title: 'Typography',
                    description: 'Font sizes, weights, and text styles — all via methods.',
                    demo: UI::column()->gap(Unit::small())->content(
                        UI::text('Extra Large Bold')
                            ->fontSize(FontSize::ExtraLarge)
                            ->weight(FontWeight::Bold)
                            ->color(Color::slate(900)),
                        UI::text('Large Semibold')
                            ->fontSize(FontSize::Large)
                            ->weight(FontWeight::SemiBold)
                            ->color(Color::slate(700)),
                        UI::text('Base Normal')
                            ->fontSize(FontSize::Base)
                            ->color(Color::slate(600)),
                        UI::text('Small Light Italic')
                            ->fontSize(FontSize::Small)
                            ->light()
                            ->italic()
                            ->color(Color::slate(400)),
                        UI::text('Monospaced Code')
                            ->mono()
                            ->fontSize(FontSize::Small)
                            ->color(Color::indigo(600)),
                    ),
                    code: <<<'PHP'
UI::text('Title')
    ->fontSize(FontSize::ExtraLarge)
    ->weight(FontWeight::Bold)
    ->color(Color::slate(900))

UI::text('Monospaced')
    ->mono()
    ->color(Color::indigo(600))
PHP
                ),

                new DemoCard(
                    title: 'Color Palette',
                    description: 'Named colors with shade levels. No hex codes needed.',
                    demo: UI::column()->gap(Unit::small())->content(
                        $this->colorRow('Indigo', fn($s) => Color::indigo($s)),
                        $this->colorRow('Emerald', fn($s) => Color::emerald($s)),
                        $this->colorRow('Rose', fn($s) => Color::rose($s)),
                        $this->colorRow('Amber', fn($s) => Color::amber($s)),
                    ),
                    code: <<<'PHP'
Color::indigo(500)          // Default shade
Color::emerald(300)         // Lighter
Color::rose(700)            // Darker

// With modifiers (passed as a Pseudo argument at the call site):
->background(Color::indigo(500), Pseudo::hover())  // :hover state
->background(Color::slate(800), Pseudo::dark())    // Dark mode
PHP
                ),

                new DemoCard(
                    title: 'Badges & Pills',
                    description: 'Pre-styled utility components.',
                    demo: UI::row()->gap(Unit::small())->wrap()->content(
                        UI::badge('New')
                            ->background(Color::blue(100))
                            ->color(Color::blue(700)),
                        UI::badge('Sale')
                            ->background(Color::red(100))
                            ->color(Color::red(700)),
                        UI::badge('Featured')
                            ->background(Color::emerald(100))
                            ->color(Color::emerald(700)),
                        UI::pill('PHP 8.3')
                            ->background(Color::violet(100))
                            ->color(Color::violet(700)),
                        UI::pill('Server-side')
                            ->background(Color::amber(100))
                            ->color(Color::amber(700)),
                    ),
                    code: <<<'PHP'
UI::badge('New')
    ->background(Color::blue(100))
    ->color(Color::blue(700))

UI::pill('PHP 8.3')
    ->background(Color::violet(100))
    ->color(Color::violet(700))
PHP
                ),
            );
    }

    // ── Interactive Tab ──

    private function interactiveTab(): VNode
    {
        return UI::column()
            ->gap(Unit::large())
            ->content(
                new DemoCard(
                    title: 'Counter (Live)',
                    description: 'Click the button — the event travels to the server and back.',
                    demo: UI::row()
                        ->gap(Unit::medium())
                        ->alignMiddle()
                        ->content(
                            UI::text('Count: ' . $this->counter)
                                ->fontSize(FontSize::TwoXL)
                                ->weight(FontWeight::Bold)
                                ->color(Color::indigo(700)),
                            UI::button('+1')
                                ->primary()
                                ->onClick(fn() => $this->counter++),
                            UI::button('-1')
                                ->secondary()
                                ->onClick(fn() => $this->counter = max(0, $this->counter - 1)),
                            UI::button('Reset')
                                ->ghost()
                                ->onClick(fn() => $this->counter = 0),
                        ),
                    code: <<<'PHP'
private int $counter = 0;

protected function initialize(): void
{
    $this->useState($this->counter);
}

protected function build(): VNode
{
    return UI::row()->alignMiddle()->content(
        UI::text("Count: {$this->counter}")
            ->fontSize(FontSize::TwoXL),
        UI::button('+1')
            ->primary()
            ->onClick(fn() => $this->counter++),
    );
}
PHP
                ),

                new DemoCard(
                    title: 'Button Variants',
                    description: 'Semantic button styles — primary, secondary, danger, ghost.',
                    demo: UI::row()->gap(Unit::small())->wrap()->content(
                        UI::button('Primary')->primary(),
                        UI::button('Secondary')->secondary(),
                        UI::button('Danger')->danger(),
                        UI::button('Success')->success(),
                        UI::button('Outline')->outline(),
                        UI::button('Ghost')->ghost(),
                        UI::button('Disabled')->primary()->disabled(),
                    ),
                    code: <<<'PHP'
UI::button('Primary')->primary()
UI::button('Danger')->danger()
UI::button('Ghost')->ghost()
UI::button('Disabled')->primary()->disabled()
PHP
                ),

                new DemoCard(
                    title: 'Conditional Rendering',
                    description: 'Show/hide elements based on server state.',
                    demo: UI::column()->gap(Unit::small())->content(
                        UI::text($this->counter > 0
                            ? "Counter is at {$this->counter} — click reset above to hide this."
                            : 'Click +1 above to reveal a message.')
                            ->color($this->counter > 0 ? Color::emerald(600) : Color::slate(400))
                            ->fontSize(FontSize::Small),
                        ...($this->counter >= 5
                            ? [UI::badge('Achievement: reached 5!')
                                ->background(Color::amber(100))
                                ->color(Color::amber(700))]
                            : []),
                    ),
                    code: <<<'PHP'
// Conditional content with PHP
UI::text($this->count > 0 ? "Active" : "Inactive")

// Conditional elements with spread
...($this->count >= 5
    ? [UI::badge('Achievement!')]
    : [])
PHP
                ),
            );
    }

    // ── Patterns Tab ──

    private function patternsTab(): VNode
    {
        return UI::column()
            ->gap(Unit::large())
            ->content(
                new DemoCard(
                    title: 'Component Composition',
                    description: 'Build complex UIs from simple, reusable components.',
                    demo: UI::column()->gap(Unit::small())->content(
                        $this->userCard('Alice', 'Engineer', Color::blue(500)),
                        $this->userCard('Bob', 'Designer', Color::emerald(500)),
                        $this->userCard('Carol', 'PM', Color::violet(500)),
                    ),
                    code: <<<'PHP'
// Reusable component
class UserCard extends Component
{
    public function __construct(
        private string $name,
        private string $role,
        private Color $accent,
    ) {}

    protected function build(): VNode
    {
        return UI::row()
            ->gap(Unit::medium())
            ->alignMiddle()
            ->padding(Unit::medium())
            ->background(Color::white())
            ->rounded(Unit::roundedLg())
            ->content(
                UI::avatar(/* src */)
                    ->size(Unit::px(40))
                    ->background($this->accent),
                UI::column()->content(
                    UI::text($this->name)->weight(FontWeight::SemiBold),
                    UI::text($this->role)->fontSize(FontSize::Small)
                        ->color(Color::slate(500)),
                ),
            );
    }
}
PHP
                ),

                new DemoCard(
                    title: 'List Rendering',
                    description: 'Map PHP arrays to UI elements. Keys enable efficient diffing.',
                    demo: UI::column()->gap(Unit::xs())->content(
                        ...array_map(fn($item) =>
                            UI::row()
                                ->key($item)
                                ->alignMiddle()
                                ->alignBetween()
                                ->padding(Unit::small())
                                ->background(Color::white())
                                ->rounded(Unit::roundedSm())
                                ->content(
                                    UI::text($item)->fontSize(FontSize::Small),
                                    UI::badge('PHP')
                                        ->background(Color::indigo(100))
                                        ->color(Color::indigo(600)),
                                ),
                            ['Components', 'State Management', 'Routing', 'DOM Patching', 'CSS Generation']
                        )
                    ),
                    code: <<<'PHP'
$items = ['Components', 'State', 'Routing'];

UI::column()->content(
    ...array_map(fn($item) =>
        UI::row()
            ->key($item) // Efficient diffing
            ->content(UI::text($item)),
        $items
    )
)
PHP
                ),

                new DemoCard(
                    title: 'Callbacks & Communication',
                    description: 'Props down, callbacks up — closures capture parent state.',
                    demo: UI::text('See the counter demo above — the +1/-1/Reset buttons modify parent state through closures.')
                        ->color(Color::slate(500))
                        ->fontSize(FontSize::Small),
                    code: <<<'PHP'
// Parent passes callback to child
class Parent extends Component
{
    private int $count = 0;

    protected function build(): VNode
    {
        return new ChildButton(
            label: "Count: {$this->count}",
            // Closure captures $this from parent
            onClick: fn() => $this->count++
        );
    }
}

class ChildButton extends Component
{
    public function __construct(
        private string $label,
        private Closure $onClick,
    ) {}

    protected function build(): VNode
    {
        return UI::button($this->label)
            ->primary()
            ->onClick($this->onClick);
    }
}
PHP
                ),
            );
    }

    // ── Helpers ──

    private function colorBox(string $label, Color $bg): VNode
    {
        return UI::row()
            ->center()
            ->size(Unit::px(50))
            ->background($bg)
            ->rounded(Unit::roundedSm())
            ->content(
                UI::text($label)
                    ->color(Color::white())
                    ->weight(FontWeight::Bold)
                    ->fontSize(FontSize::Small)
            );
    }

    private function colorRow(string $name, callable $colorFn): VNode
    {
        $shades = [100, 200, 300, 400, 500, 600, 700, 800, 900];
        $swatches = [];
        foreach ($shades as $shade) {
            $swatches[] = UI::column()
                ->size(Unit::px(32))
                ->background($colorFn($shade))
                ->rounded(Unit::roundedSm());
        }

        return UI::row()
            ->gap(Unit::xs())
            ->alignMiddle()
            ->content(
                UI::text($name)
                    ->fontSize(FontSize::ExtraSmall)
                    ->color(Color::slate(500))
                    ->width(Unit::px(60)),
                ...$swatches
            );
    }

    private function userCard(string $name, string $role, Color $accent): VNode
    {
        return UI::row()
            ->gap(Unit::medium())
            ->alignMiddle()
            ->padding(Unit::medium())
            ->background(Color::white())
            ->rounded(Unit::roundedLg())
            ->shadow(Shadow::Small)
            ->content(
                UI::row()
                    ->center()
                    ->size(Unit::px(40))
                    ->background($accent)
                    ->roundedFull()
                    ->content(
                        UI::text(mb_substr($name, 0, 1))
                            ->color(Color::white())
                            ->weight(FontWeight::Bold)
                    ),
                UI::column()->content(
                    UI::text($name)
                        ->weight(FontWeight::SemiBold)
                        ->color(Color::slate(800)),
                    UI::text($role)
                        ->fontSize(FontSize::Small)
                        ->color(Color::slate(500)),
                ),
            );
    }
}
