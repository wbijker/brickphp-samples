<?php

namespace Samples\Docs\Components;

use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;

/**
 * VS Code-style window chrome — dark slate body, three gray traffic-light
 * dots, optional active tab with a brand-orange bottom border. Holds a
 * single content slot.
 *
 *   (new CodeWindow())->tab('index.php')->content(new PHPCode($src));
 */
class CodeWindow extends Component
{
    /** @var array<int, VNode|string|null> */
    private array $children = [];
    private ?string $tab = null;
    private VNode|string|null $tabIcon = null;

    /**
     * Show an "active tab" inside the title bar (right of the dots).
     * Mirrors VS Code's open-file tab — small monospace label with a
     * 2-px orange bottom accent. The optional `$icon` accepts either
     * a plain string (rendered as text, e.g. an emoji like `'⚡'`) or
     * any VNode (rendered as-is, e.g. a `new PHPIcon()` SVG mark).
     */
    public function tab(string $label, VNode|string|null $icon = null): static
    {
        $this->tab = $label;
        $this->tabIcon = $icon;
        return $this;
    }

    public function content(VNode|string|null ...$children): static
    {
        $this->children = array_merge($this->children, $children);
        return $this;
    }

    protected function build(): VNode
    {
        return UI::column()
            ->rounded(Unit::roundedXl())
            ->overflow()
            ->shadow(Shadow::TwoXL)
            ->bordered()
            ->borderColor(Color::slate(800))
            ->background(Color::slate(950))
            ->content(
                $this->chrome(),
                UI::column()
                    ->background(Color::slate(950))
                    ->content(...$this->children),
            );
    }

    private function chrome(): UIElement
    {
        $row = UI::row()
            ->background(Color::slate(900))
            ->borderBottom(1)
            ->borderColor(Color::slate(800))
            ->paddingX(Unit::px(16))
            ->alignMiddle()
            ->content(
                UI::row()->gap(Unit::px(6))->paddingY(Unit::px(10))->alignMiddle()->content(
                    $this->dot(),
                    $this->dot(),
                    $this->dot(),
                ),
            );

        if ($this->tab !== null) {
            $row = $row->content($this->tabPill());
        }

        return $row;
    }

    private function tabPill(): UIElement
    {
        $children = [];
        if ($this->tabIcon instanceof VNode) {
            $children[] = $this->tabIcon;
        } elseif (is_string($this->tabIcon)) {
            $children[] = UI::span($this->tabIcon)
                ->color(Color::slate(400));
        }
        $children[] = UI::text($this->tab)
            ->fontSize(FontSize::ExtraSmall)
            ->weight(FontWeight::Medium)
            ->color(Color::white());

        return UI::row()
            ->marginLeft(Unit::px(16))
            ->paddingX(Unit::px(12))
            ->paddingY(Unit::px(10))
            ->gap(Unit::px(8))
            ->alignMiddle()
            ->background(Color::slate(950))
            ->borderBottom(2)
            ->borderColor(Color::orange(500))
            ->content(...$children);
    }

    private function dot(): UIElement
    {
        return UI::container()
            ->width(Unit::px(12))
            ->height(Unit::px(12))
            ->roundedFull()
            ->background(Color::slate(700));
    }
}
