<?php

namespace Samples\TodoApp;

use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;

class TodoList extends Component
{
    /** @var Todo[] */
    private array $todos = [];
    private int $nextId = 1;
    private string $filter = 'all';
    private string $inputText = '';

    protected function initialize(): void
    {
        $this->useState($this->todos, class: Todo::class);
        $this->useState($this->nextId);
        $this->useState($this->filter);
        $this->useState($this->inputText);
    }

    protected function build(): VNode
    {
        $filtered = $this->getFilteredTodos();
        $activeCount = count(array_filter($this->todos, fn(Todo $t) => !$t->completed));
        $completedCount = count($this->todos) - $activeCount;
        $allCompleted = count($this->todos) > 0 && $activeCount === 0;

        return UI::column()
            ->extend(true)
            ->background(Color::gray(50))
            ->content(
                // Analog clock
                UI::row()
                    ->alignCenter()
                    ->paddingY(Unit::rem(1.5))
                    ->content(new Clock()),

                // Title
                UI::text('todos')
                    ->center()
                    ->fontSize(FontSize::SixXL)
                    ->weight(FontWeight::Thin)
                    ->color(Color::red(700))
                    ->opacity(20)
                    ->paddingY(Unit::rem(1)),

                // Current date/time, aligned to the right edge of the card
                UI::row()
                    ->alignRight()
                    ->maxWidth(Unit::px(550))
                    ->width(Unit::full())
                    ->marginX(Unit::auto())
                    ->paddingY(Unit::rem(0.5))
                    ->content(
                        UI::text(date('Y-m-d H:i:s'))
                            ->fontSize(FontSize::Small)
                            ->color(Color::neutral(500))
                            ->invalidateText()
                    ),

                // Main card
                UI::column()
                    ->background(Color::gray(), Pseudo::hover()->active()->sm())
                    ->maxWidth(Unit::px(550))
                    ->width(Unit::full())
                    ->marginX(Unit::auto())
                    ->shadow(Shadow::Large)
                    ->content(
                        $this->buildInput($allCompleted),
                        ...(count($this->todos) > 0
                            ? [$this->buildList($filtered), $this->buildFooter($activeCount, $completedCount)]
                            : [])
                    ),

                // Info footer
                UI::column()
                    ->alignCenter()
                    ->padding(Unit::rem(1.5))
                    ->gap(Unit::rem(0.25))
                    ->content(
                        UI::text('Double-click to edit a todo')
                            ->fontSize(FontSize::Small)
                            ->color(Color::neutral(500)),
                        UI::text('Part of TodoMVC')
                            ->fontSize(FontSize::Small)
                            ->color(Color::neutral(500))
                    )
            );
    }

    private function buildInput(bool $allCompleted): UIElement
    {
        $children = [];

        if (count($this->todos) > 0) {
            $children[] = UI::span()
                ->clickable()
                ->padding(Unit::rem(0.7))
                ->fontSize(FontSize::Large)
                ->color($allCompleted ? Color::neutral(700) : Color::neutral(200))
                ->onClick(function () {
                    $allActive = count(array_filter($this->todos, fn(Todo $t) => !$t->completed)) > 0;
                    foreach ($this->todos as $todo) {
                        $todo->completed = $allActive;
                    }
                })
                ->content('❯');
        }

        $children[] = UI::input()
            ->text()
            ->placeholder('What needs to be done?')
            ->autofocus()
            ->bind($this->inputText)
            ->grow()
            ->padding(Unit::rem(1))
            ->borderNone()
            ->outlineNone()
            ->fontSize(FontSize::TwoXL)
            ->weight(FontWeight::Light)
            ->color(Color::neutral(900))
            ->onChange(function () {
                echo "We are about to add a new todo with text: " . $this->inputText . "\n";
                $text = trim($this->inputText);
                if ($text !== '') {
                    $this->todos[] = new Todo($this->nextId++, $text, false);
                    $this->inputText = '';
                }
            });

        return UI::row()
            ->alignMiddle()
            ->background(Color::white())
            ->content(...$children);
    }

    /**
     * @param Todo[] $todos
     */
    private function buildList(array $todos): UIElement
    {
        $items = [];
        foreach ($todos as $todo) {
            $items[] = new TodoItem(
                id: $todo->id,
                text: $todo->text,
                completed: $todo->completed,
                onToggle: function (int $id) {
                    foreach ($this->todos as $t) {
                        if ($t->id === $id) {
                            $t->completed = !$t->completed;
                            break;
                        }
                    }
                },
                onDestroy: function (int $id) {
                    $this->todos = array_values(array_filter(
                        $this->todos,
                        fn(Todo $t) => $t->id !== $id
                    ));
                },
                onSave: function (int $id, string $text) {
                    foreach ($this->todos as $t) {
                        if ($t->id === $id) {
                            $t->text = $text;
                            break;
                        }
                    }
                },
            );
        }

        return UI::column()->content(...$items);
    }

    private function buildFooter(int $activeCount, int $completedCount): UIElement
    {
        $itemsLabel = $activeCount === 1 ? '1 item left!' : $activeCount . ' items left!';

        return UI::row()
            ->alignMiddle()
            ->alignBetween()
            ->background(Color::white())
            ->paddingX(Unit::rem(1))
            ->paddingY(Unit::rem(0.6))
            ->borderTop()
            ->borderColor(Color::neutral(200))
            ->fontSize(FontSize::Small)
            ->color(Color::neutral(500))
            ->content(
                UI::text($itemsLabel)
                    ->minWidth(Unit::rem(8)),

                UI::row()
                    ->gap(Unit::rem(0.25))
                    ->content(
                        $this->buildFilter('All', 'all'),
                        $this->buildFilter('Active', 'active'),
                        $this->buildFilter('Completed', 'completed'),
                    ),

                $completedCount > 0
                    ? UI::button('Clear completed')
                        ->clickable()
                        ->borderNone()
                        ->background(Color::transparent())
                        ->fontSize(FontSize::Small)
                        ->color(Color::neutral(500))
                        ->minWidth(Unit::rem(8))
                        ->onClick(function () {
                            $this->todos = array_values(array_filter(
                                $this->todos,
                                fn(Todo $t) => !$t->completed
                            ));
                        })
                    : UI::span()->minWidth(Unit::rem(8))
            );
    }

    private function buildFilter(string $label, string $filter): UIElement
    {
        $isActive = $this->filter === $filter;

        $btn = UI::button($label)
            ->clickable()
            ->borderNone()
            ->background(Color::transparent())
            ->padding(Unit::rem(0.2))
            ->paddingX(Unit::rem(0.45))
            ->rounded(Unit::px(3))
            ->fontSize(FontSize::Small)
            ->color(Color::inherit())
            ->onClick(function () use ($filter) {
                $this->filter = $filter;
            });

        if ($isActive) {
            $btn = $btn
                ->bordered()
                ->borderColor(Color::red(700)->alpha(0.2));
        }

        return $btn;
    }

    /**
     * @return Todo[]
     */
    private function getFilteredTodos(): array
    {
        return match ($this->filter) {
            'active' => array_values(array_filter($this->todos, fn(Todo $t) => !$t->completed)),
            'completed' => array_values(array_filter($this->todos, fn(Todo $t) => $t->completed)),
            default => $this->todos,
        };
    }
}
