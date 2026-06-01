<?php

namespace Samples\Docs\Components;

use BrickPHP\UI\Color;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;

/**
 * Generic card tile from the design system: white surface, 1px subtle
 * border (no shadow), optional 2px top accent stripe in the brand color,
 * single content slot. Used everywhere a "card" appears on the site —
 * feature cards, preview blocks, side panels.
 *
 *   (new Tile())
 *       ->accent()                  // 2px orange top stripe
 *       ->padding(Unit::px(32))     // override default 32px padding
 *       ->content(...);             // any children
 */
class Tile extends Component
{
    /** @var array<int, VNode|string|null> */
    private array $children = [];
    private bool $accent = false;
    private ?Unit $paddingValue = null;

    public function __construct()
    {
        $this->paddingValue = Unit::px(32);
    }

    /** Show a 2px brand-color stripe along the top edge of the tile. */
    public function accent(): static
    {
        $this->accent = true;
        return $this;
    }

    /** Override the default 32px inner padding. */
    public function padding(Unit $value): static
    {
        $this->paddingValue = $value;
        return $this;
    }

    public function content(VNode|string|null ...$children): static
    {
        $this->children = array_merge($this->children, $children);
        return $this;
    }

    protected function build(): VNode
    {
        $body = UI::column()
            ->padding($this->paddingValue)
            ->gap(Unit::px(12))
            ->content(...$this->children);

        $tile = UI::column()
            ->background(Color::white())
            ->bordered()
            ->borderColor(Color::slate(200))
            ->borderColor(Color::orange(300), Pseudo::hover())
            ->rounded(Unit::rounded());

        if ($this->accent) {
            return $tile->content($this->accentStripe(), $body);
        }
        return $tile->content($body);
    }

    private function accentStripe(): UIElement
    {
        return UI::container()
            ->height(Unit::px(2))
            ->width(Unit::full())
            ->background(Color::orange(500));
    }
}
