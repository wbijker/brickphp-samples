<?php

namespace Samples\TodoApp;

use Closure;
use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;

class TodoItem extends Component
{
    private bool $editing = false;
    private string $editText = '';

    public function __construct(
        private int     $id,
        private string  $text,
        private bool    $completed,
        private Closure $onToggle,
        private Closure $onDestroy,
        private Closure $onSave,
    ) {}

    protected function initialize(): void
    {
        $this->useState($this->editing);
        $this->useState($this->editText);
    }

    protected function build(): VNode
    {
        $row = UI::row()
            ->key((string)$this->id)
            ->alignMiddle()
            ->background(Color::white())
            ->paddingX(Unit::rem(1))
            ->paddingY(Unit::rem(0.75))
            ->borderTop()
            ->borderColor(Color::neutral(200));

        if ($this->editing) {
            return $row->content(
                $this->buildEditInput(),
                $this->buildSaveButton(),
                $this->buildCancelButton(),
            );
        }

        return $row->content(
            $this->buildCheckbox(),
            $this->buildLabel(),
            $this->buildDestroyButton(),
        );
    }

    private function buildCheckbox(): UIElement
    {
        $borderColor = $this->completed ? Color::green(100) : Color::neutral(400);

        return UI::row()
            ->center()
            ->size(Unit::rem(2))
            ->noShrink()
            ->bordered()
            ->borderColor($borderColor)
            ->roundedFull()
            ->clickable()
            ->onClick(fn() => ($this->onToggle)($this->id))
            ->content(
                $this->completed
                    ? UI::text('✓')
                        ->color(Color::teal(400))
                        ->fontSize(FontSize::Large)
                    : ''
            );
    }

    private function buildLabel(): UIElement
    {
        $label = UI::text($this->text)
            ->grow()
            ->paddingX(Unit::rem(0.5))
            ->fontSize(FontSize::TwoXL)
            ->weight(FontWeight::Light)
            ->onDblClick(function () {
                $this->editing = true;
                $this->editText = $this->text;
            });

        if ($this->completed) {
            return $label
                ->color(Color::neutral(400))
                ->strikethrough();
        }

        return $label->color(Color::neutral(700));
    }

    private function buildDestroyButton(): UIElement
    {
        return UI::button('×')
            ->clickable()
            ->borderNone()
            ->background(Color::transparent())
            ->paddingX(Unit::rem(0.5))
            ->fontSize(FontSize::TwoXL)
            ->color(Color::rose(300))
            ->color(Color::rose(500), Pseudo::hover())
            ->onClick(fn() => ($this->onDestroy)($this->id));
    }

    private function buildEditInput(): UIElement
    {
        return UI::input()
            ->text()
            ->autofocus()
            ->bind($this->editText)
            ->grow()
            ->paddingX(Unit::rem(0.5))
            ->paddingY(Unit::rem(0.25))
            ->bordered()
            ->borderColor(Color::neutral(300))
            ->rounded(Unit::rem(0.25))
            ->outlineNone()
            ->fontSize(FontSize::TwoXL)
            ->weight(FontWeight::Light)
            ->color(Color::neutral(700));
    }

    private function buildSaveButton(): UIElement
    {
        return UI::button('✓')
            ->clickable()
            ->borderNone()
            ->background(Color::transparent())
            ->paddingX(Unit::rem(0.5))
            ->fontSize(FontSize::TwoXL)
            ->color(Color::teal(400))
            ->color(Color::teal(500), Pseudo::hover())
            ->onClick(function () {
                $next = trim($this->editText);
                if ($next !== '') {
                    ($this->onSave)($this->id, $next);
                }
                $this->editing = false;
                $this->editText = '';
            });
    }

    private function buildCancelButton(): UIElement
    {
        return UI::button('✗')
            ->clickable()
            ->borderNone()
            ->background(Color::transparent())
            ->paddingX(Unit::rem(0.5))
            ->fontSize(FontSize::TwoXL)
            ->color(Color::rose(300))
            ->color(Color::rose(500), Pseudo::hover())
            ->onClick(function () {
                $this->editing = false;
                $this->editText = '';
            });
    }
}
