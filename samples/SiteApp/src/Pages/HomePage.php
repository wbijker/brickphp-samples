<?php

namespace Samples\SiteApp\Pages;

use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Router;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;

class HomePage extends Component
{

    protected function build(): VNode
    {
        return UI::column()
            ->content(
                $this->buildHero(),
                $this->buildPrinciples(),
                $this->buildQuickDemo(),
            );
    }

    private function buildHero(): VNode
    {
        return UI::column()
            ->background(Color::indigo(600))
            ->padding(Unit::extraLarge())
            ->paddingY(Unit::px(80))
            ->alignCenter()
            ->gap(Unit::large())
            ->content(
                UI::text('Server-Powered Web Applications')
                    ->fontSize(FontSize::FourXL)
                    ->weight(FontWeight::Bold)
                    ->color(Color::white())
                    ->center(),
                UI::text('Build reactive UIs entirely in PHP. No JavaScript frameworks. No build steps. No Node.js.')
                    ->fontSize(FontSize::Large)
                    ->color(Color::indigo(200))
                    ->center()
                    ->maxWidth(Unit::px(600)),
                UI::row()
                    ->gap(Unit::medium())
                    ->content(
                        UI::button('Explore Features')
                            ->background(Color::white())
                            ->color(Color::indigo(700))
                            ->borderNone()
                            ->padding(Unit::small())
                            ->paddingX(Unit::large())
                            ->rounded(Unit::roundedLg())
                            ->weight(FontWeight::SemiBold)
                            ->clickable()
                            ->onClick(fn() => Router::navigate('/features')),
                        UI::button('See Components')
                            ->background(Color::transparent())
                            ->color(Color::white())
                            ->bordered()
                            ->borderColor(Color::indigo(300))
                            ->padding(Unit::small())
                            ->paddingX(Unit::large())
                            ->rounded(Unit::roundedLg())
                            ->weight(FontWeight::SemiBold)
                            ->clickable()
                            ->onClick(fn() => Router::navigate('/components')),
                    )
            );
    }

    private function buildPrinciples(): VNode
    {
        return UI::column()
            ->maxWidth(Unit::px(900))
            ->marginX(Unit::auto())
            ->padding(Unit::extraLarge())
            ->gap(Unit::large())
            ->content(
                UI::text('Core Principles')
                    ->fontSize(FontSize::TwoXL)
                    ->weight(FontWeight::Bold)
                    ->color(Color::slate(900))
                    ->center(),
                UI::grid(3)
                    ->gap(Unit::large())
                    ->content(
                        $this->principleCard(
                            'All Server-Side PHP',
                            'Components, state, events, routing — everything runs on the server. The client only applies DOM patches.',
                            Color::blue(500)
                        ),
                        $this->principleCard(
                            'Declarative UI',
                            'Build GUIs by what they do, not how to style them. CSS is generated automatically from semantic methods.',
                            Color::emerald(500)
                        ),
                        $this->principleCard(
                            'Zero Frontend Tooling',
                            'No npm, no webpack, no build steps. Ship a single PHP file and a tiny JS runtime (~3KB).',
                            Color::violet(500)
                        ),
                    )
            );
    }

    private function principleCard(string $title, string $description, Color $accent): VNode
    {
        return UI::column()
            ->background(Color::white())
            ->padding(Unit::large())
            ->rounded(Unit::roundedXl())
            ->shadow(Shadow::Small)
            ->gap(Unit::small())
            ->borderTop()
            ->borderColor($accent)
            ->content(
                UI::text($title)
                    ->fontSize(FontSize::Large)
                    ->weight(FontWeight::SemiBold)
                    ->color(Color::slate(800)),
                UI::text($description)
                    ->fontSize(FontSize::Small)
                    ->color(Color::slate(500)),
            );
    }

    private function buildQuickDemo(): VNode
    {
        return UI::column()
            ->background(Color::slate(900))
            ->padding(Unit::extraLarge())
            ->paddingY(Unit::px(60))
            ->gap(Unit::large())
            ->alignCenter()
            ->content(
                UI::text('What does it look like?')
                    ->fontSize(FontSize::TwoXL)
                    ->weight(FontWeight::Bold)
                    ->color(Color::white()),
                UI::pre()
                    ->maxWidth(Unit::px(700))
                    ->width(Unit::full())
                    ->background(Color::slate(800))
                    ->padding(Unit::large())
                    ->rounded(Unit::roundedLg())
                    ->content(
                        UI::code()
                            ->fontSize(FontSize::Small)
                            ->color(Color::emerald(300))
                            ->content($this->getSampleCode())
                    ),
                UI::text('This PHP code produces a fully interactive counter — no JS written by you.')
                    ->color(Color::slate(400))
                    ->fontSize(FontSize::Small)
                    ->center(),
            );
    }

    private function getSampleCode(): string
    {
        return <<<'PHP'
class Counter extends Component
{
    private int $count = 0;

    protected function initialize(): void
    {
        $this->useState($this->count);
    }

    protected function build(): VNode
    {
        return UI::column()
            ->gap(Unit::medium())
            ->alignCenter()
            ->content(
                UI::text("Count: {$this->count}")
                    ->fontSize(FontSize::TwoXL)
                    ->weight(FontWeight::Bold),

                UI::button('Increment')
                    ->primary()
                    ->onClick(fn() => $this->count++)
            );
    }
}
PHP;
    }
}
