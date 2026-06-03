<?php

namespace Samples\Docs\Components;

use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\Svg;
use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use HeroIcons\HeroIcons;

/**
 * One landing-page feature card built on top of the generic Tile. Standard
 * layout: icon, headline + "Read more" link, body copy, then a
 * caller-supplied "preview" body (a code window, a HMR ticker, a
 * wireframe sketch — anything). The caller passes the HeroIcons SVG; the
 * tile sets its size and color.
 */
class FeatureTile extends Component
{
    /** @var array<int, VNode|string|null> */
    private array $previewChildren = [];

    public function __construct(
        private Svg $icon,
        private string $title,
        private string $body,
        private string $href = '#',
    ) {}

    /** Whatever you pass renders inside the dark preview block at the bottom. */
    public function preview(VNode|string|null ...$children): static
    {
        $this->previewChildren = array_merge($this->previewChildren, $children);
        return $this;
    }

    protected function build(): VNode
    {
        return (new Tile())
            ->accent()
            ->content(
                $this->icon->svgWidth('28')->svgHeight('28')->color(Color::orange(500)),
                $this->titleRow(),
                UI::text($this->body)
                    ->fontSize(FontSize::Small)
                    ->color(Color::slate(500)),
                ...$this->previewBlock(),
            );
    }

    private function titleRow(): VNode
    {
        return UI::row()
            ->alignBetween()
            ->alignMiddle()
            ->gap(Unit::px(8))
            ->paddingTop(Unit::px(4))
            ->content(
                UI::text($this->title)
                    ->fontSize(FontSize::Large)
                    ->weight(FontWeight::SemiBold)
                    ->color(Color::slate(900)),
                $this->readMore(),
            );
    }

    private function readMore(): VNode
    {
        return UI::row()
            ->gap(Unit::px(2))
            ->alignMiddle()
            ->clickable()
            ->content(
                UI::link($this->href, 'Read more')
                    ->fontSize(FontSize::ExtraSmall)
                    ->weight(FontWeight::SemiBold)
                    ->color(Color::orange(500))
                    ->color(Color::orange(600), Pseudo::hover()),
                HeroIcons::ChevronRight('none', 1.5, 'currentColor', '')
                    ->svgWidth('14')->svgHeight('14')
                    ->color(Color::orange(500)),
            );
    }

    /**
     * The preview slot is a thin wrapper — we add top margin so it
     * separates from the body copy, but no background of our own so
     * children (typically a `CodeWindow`, which has its own dark
     * chrome) don't end up nested inside a duplicate dark box. Plain
     * non-code previews can supply their own background.
     *
     * @return array<int, VNode>
     */
    private function previewBlock(): array
    {
        if ($this->previewChildren === []) {
            return [];
        }

        return [
            UI::column()
                ->marginTop(Unit::px(16))
                ->content(...$this->previewChildren),
        ];
    }
}
