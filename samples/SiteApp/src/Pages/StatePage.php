<?php

namespace Samples\SiteApp\Pages;

use BrickPHP\Events\InputEvent;
use Samples\SiteApp\Components\CodeBlock;
use Samples\SiteApp\Components\DemoCard;
use Samples\SiteApp\Components\SectionHeading;
use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;

class StatePage extends Component
{
    /** @var array<int, array{id: int, text: string, done: bool}> */
    private array $todos = [];
    private int $nextId = 1;
    private string $newTodo = '';

    protected function initialize(): void
    {
        $this->useState($this->todos);
        $this->useState($this->nextId);
        $this->useState($this->newTodo);
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
                    'State Management',
                    'Server-side state with automatic persistence across interactions.'
                ),
                $this->todoDemo(),
                $this->stateExplanation(),
                $this->architectureOverview(),
            );
    }

    private function todoDemo(): VNode
    {
        $doneCount = count(array_filter($this->todos, fn($t) => $t['done']));
        $totalCount = count($this->todos);

        return new DemoCard(
            title: 'Mini Todo (Live)',
            description: 'A fully working todo app — state persists in PHP session.',
            demo: UI::column()->gap(Unit::medium())->content(
                // Input row
                UI::row()->gap(Unit::small())->content(
                    UI::input()
                        ->text()
                        ->placeholder('Add a task...')
                        ->value($this->newTodo)
                        ->grow()
                        ->padding(Unit::small())
                        ->bordered()
                        ->borderColor(Color::slate(300))
                        ->rounded(Unit::roundedLg())
                        ->onChange(fn(InputEvent $e) => $this->newTodo = $e->value ?? ''),
                    UI::button('Add')
                        ->primary()
                        ->onClick(function () {
                            $text = trim($this->newTodo);
                            if ($text !== '') {
                                $this->todos[] = [
                                    'id' => $this->nextId++,
                                    'text' => $text,
                                    'done' => false,
                                ];
                                $this->newTodo = '';
                            }
                        }),
                ),
                // Stats
                ...($totalCount > 0
                    ? [UI::text("{$doneCount}/{$totalCount} completed")
                        ->fontSize(FontSize::ExtraSmall)
                        ->color(Color::slate(400))]
                    : []),
                // List
                ...array_map(fn($todo) =>
                    UI::row()
                        ->key("todo-{$todo['id']}")
                        ->alignMiddle()
                        ->alignBetween()
                        ->padding(Unit::small())
                        ->paddingX(Unit::medium())
                        ->background($todo['done'] ? Color::slate(50) : Color::white())
                        ->rounded(Unit::roundedLg())
                        ->bordered()
                        ->borderColor(Color::slate(200))
                        ->content(
                            UI::row()->gap(Unit::small())->alignMiddle()->grow()->content(
                                UI::row()
                                    ->center()
                                    ->size(Unit::px(24))
                                    ->bordered()
                                    ->borderColor($todo['done'] ? Color::emerald(400) : Color::slate(300))
                                    ->roundedFull()
                                    ->clickable()
                                    ->onClick(function () use ($todo) {
                                        foreach ($this->todos as &$t) {
                                            if ($t['id'] === $todo['id']) {
                                                $t['done'] = !$t['done'];
                                            }
                                        }
                                    })
                                    ->content(
                                        $todo['done']
                                            ? UI::text('✓')
                                                ->color(Color::emerald(500))
                                                ->fontSize(FontSize::ExtraSmall)
                                            : ''
                                    ),
                                UI::text($todo['text'])
                                    ->fontSize(FontSize::Small)
                                    ->color($todo['done'] ? Color::slate(400) : Color::slate(700))
                            ),
                            UI::button('x')
                                ->borderNone()
                                ->background(Color::transparent())
                                ->color(Color::slate(400))
                                ->color(Color::red(500), Pseudo::hover())
                                ->clickable()
                                ->fontSize(FontSize::Small)
                                ->onClick(function () use ($todo) {
                                    $this->todos = array_values(array_filter(
                                        $this->todos,
                                        fn($t) => $t['id'] !== $todo['id']
                                    ));
                                }),
                        ),
                    $this->todos
                ),
                // Clear all
                ...($doneCount > 0
                    ? [UI::button("Clear {$doneCount} completed")
                        ->ghost()
                        ->fontSize(FontSize::Small)
                        ->onClick(function () {
                            $this->todos = array_values(array_filter(
                                $this->todos,
                                fn($t) => !$t['done']
                            ));
                        })]
                    : []),
            ),
            code: <<<'PHP'
class TodoDemo extends Component
{
    private array $todos = [];
    private int $nextId = 1;

    protected function initialize(): void
    {
        // State survives across interactions
        $this->useState($this->todos);
        $this->useState($this->nextId);
    }

    // Add item
    fn() => $this->todos[] = [
        'id' => $this->nextId++,
        'text' => $text,
        'done' => false,
    ];

    // Toggle item
    fn() => $this->todos[$i]['done'] =
        !$this->todos[$i]['done'];

    // Remove item
    fn() => $this->todos = array_values(
        array_filter($this->todos,
            fn($t) => $t['id'] !== $id)
    );
}
PHP
        );
    }

    private function stateExplanation(): VNode
    {
        return UI::column()
            ->gap(Unit::medium())
            ->content(
                UI::text('State Storage Backends')
                    ->fontSize(FontSize::ExtraLarge)
                    ->weight(FontWeight::Bold)
                    ->color(Color::slate(800)),
                UI::grid(2)->gap(Unit::medium())->content(
                    $this->storageCard(
                        'Session (Default)',
                        'Server-side PHP session. State survives page reloads. Cleared when session expires.',
                        '$this->useState($this->data)',
                        Color::blue(500),
                    ),
                    $this->storageCard(
                        'localStorage',
                        'Client-side persistent storage. Survives browser close. Synced to server on each interaction.',
                        '$this->useState($this->data, StateManagers::$localStorage)',
                        Color::emerald(500),
                    ),
                    $this->storageCard(
                        'sessionStorage',
                        'Client-side tab-scoped storage. Cleared when tab closes.',
                        '$this->useState($this->data, StateManagers::$sessionStorage)',
                        Color::violet(500),
                    ),
                    $this->storageCard(
                        'Cookie',
                        'HTTP cookie storage. Sent with every request. Size limited.',
                        '$this->useState($this->data, StateManagers::$cookie)',
                        Color::amber(500),
                    ),
                ),
            );
    }

    private function storageCard(string $title, string $description, string $code, Color $accent): VNode
    {
        return UI::column()
            ->background(Color::white())
            ->rounded(Unit::roundedXl())
            ->shadow(Shadow::Small)
            ->clipContent()
            ->content(
                UI::column()
                    ->borderTop()
                    ->borderColor($accent)
                    ->padding(Unit::large())
                    ->gap(Unit::small())
                    ->content(
                        UI::text($title)
                            ->weight(FontWeight::SemiBold)
                            ->color(Color::slate(800)),
                        UI::text($description)
                            ->fontSize(FontSize::Small)
                            ->color(Color::slate(500)),
                        UI::pre()
                            ->background(Color::slate(100))
                            ->padding(Unit::small())
                            ->rounded(Unit::roundedSm())
                            ->content(
                                UI::code()
                                    ->fontSize(FontSize::ExtraSmall)
                                    ->color(Color::slate(700))
                                    ->content($code)
                            ),
                    ),
            );
    }

    private function architectureOverview(): VNode
    {
        return UI::column()
            ->gap(Unit::medium())
            ->content(
                UI::text('How It Works')
                    ->fontSize(FontSize::ExtraLarge)
                    ->weight(FontWeight::Bold)
                    ->color(Color::slate(800)),
                new CodeBlock(<<<'PHP'
// The complete request lifecycle:

// 1. INITIAL LOAD (GET /)
//    Server renders full component tree → HTML + CSS + state
//    Client: displays page, attaches event listeners

// 2. USER INTERACTION (click, input change, etc.)
//    Client sends: POST / {event: "click", path: "0,2,1", value: null}

// 3. SERVER PROCESSES EVENT
//    a) Render OLD tree (restore state from session)
//    b) Find node at path → execute event closure
//    c) Closures modify component state (e.g., $this->count++)
//    d) Finalize: save state back to session
//    e) Render NEW tree (with updated state)
//    f) Diff OLD vs NEW → generate minimal DOM patches

// 4. SERVER RESPONDS
//    {patches: [...], styles: [...], state: {...}}

// 5. CLIENT APPLIES PATCHES
//    Only changed DOM nodes are updated — no full re-render
//    New CSS rules are injected
//    Client state (localStorage, etc.) is synced

// Result: reactive UI with server-side logic
// No JavaScript framework. No API layer. No build step.
PHP,
                    'Architecture'
                ),
            );
    }
}
