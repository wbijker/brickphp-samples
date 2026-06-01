<?php

namespace Samples\Docs\Sections;

use BrickPHP\UI\Color;
use BrickPHP\UI\Direction;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\StatelessComponent;
use BrickPHP\VNode\VNode;
use Samples\Docs\Components\BrowserFrame;
use Samples\Docs\Components\CodeWindow;
use Samples\Docs\Components\Icon;
use Samples\Docs\Components\PHPCode;
use Samples\Docs\Components\PHPIcon;

/**
 * "Reactive Components, No JavaScript Required" section. Two-column on
 * lg+: marketing copy + check-circle bullets on the left, Counter
 * source in a CodeWindow stacked above the live browser-frame demo on
 * the right.
 */
class PreviewSection extends StatelessComponent
{
    protected function build(): VNode
    {
        return UI::column()
            ->background(Color::white())
            ->paddingY(Unit::px(96))
            ->paddingX(Unit::px(24))
            ->content(
                UI::row()
                    ->maxWidth(Unit::px(1100))
                    ->marginX(Unit::auto())
                    ->width(Unit::full())
                    ->gap(Unit::px(64))
                    ->alignMiddle()
                    ->direction(Direction::column())
                    ->direction(Direction::row(), Pseudo::lg())
                    ->content(
                        $this->copy(),
                        $this->frame(),
                    ),
            );
    }

    private function copy(): UIElement
    {
        return UI::column()
            ->grow()
            ->gap(Unit::px(24))
            ->content(
                UI::text('Reactive Components,')
                    ->fontSize(FontSize::FourXL)
                    ->weight(FontWeight::Bold)
                    ->color(Color::slate(900))
                    ->content(
                        UI::br(),
                        UI::span('No JavaScript Required')
                            ->color(Color::orange(500)),
                        UI::text('.')->color(Color::slate(900)),
                    ),
                UI::text('The Counter example below is rendered entirely on the server. When you click a button, BrickPHP calculates the delta and patches the DOM over a persistent WebSocket connection.')
                    ->fontSize(FontSize::Large)
                    ->color(Color::slate(500)),
                UI::column()
                    ->gap(Unit::px(16))
                    ->content(
                        $this->bullet('Automatic DOM patching with Morphdom integration.'),
                        $this->bullet('Native support for form validation and error states.'),
                    ),
            );
    }

    private function bullet(string $text): UIElement
    {
        return UI::row()
            ->gap(Unit::px(12))
            ->alignTop()
            ->content(
                (new Icon('check_circle'))->color(Color::orange(500))->size(20),
                UI::text($text)
                    ->fontSize(FontSize::Small)
                    ->color(Color::slate(700))
                    ->paddingTop(Unit::px(2)),
            );
    }

    private function frame(): UIElement
    {
        $counterSource = <<<'PHP'
        class Counter extends Component
        {
            private int $count = 0;

            protected function initialize(): void
            {
                $this->useState($this->count);
            }

            protected function build(): VNode
            {
                return UI::row()->content(
                    UI::button('-')->onClick(fn() => $this->count--),
                    UI::text($this->count),
                    UI::button('+')->onClick(fn() => $this->count++),
                );
            }
        }
        PHP;

        return UI::column()
            ->grow()
            ->width(Unit::full())
            ->gap(Unit::px(24))
            ->content(
                (new CodeWindow())
                    ->tab('Counter.php', new PHPIcon())
                    ->content(new PHPCode($counterSource)),
                (new BrowserFrame())
                    ->url('localhost:8080/demo/counter')
                    ->content($this->counterDemo()),
            );
    }

    private function counterDemo(): UIElement
    {
        return UI::column()
            ->paddingY(Unit::px(48))
            ->paddingX(Unit::px(48))
            ->alignCenter()
            ->minHeight(Unit::px(320))
            ->content(
                UI::column()
                    ->maxWidth(Unit::px(280))
                    ->width(Unit::full())
                    ->alignCenter()
                    ->gap(Unit::px(16))
                    ->padding(Unit::px(32))
                    ->rounded(Unit::roundedXl())
                    ->bordered()
                    ->borderColor(Color::slate(100))
                    ->background(Color::slate(50))
                    ->content(
                        UI::text('BRICKPHP COMPONENT')
                            ->fontSize(FontSize::ExtraSmall)
                            ->weight(FontWeight::SemiBold)
                            ->color(Color::slate(500))
                            ->uppercase(),
                        UI::text('0')
                            ->fontSize(FontSize::SixXL)
                            ->weight(FontWeight::Bold)
                            ->color(Color::slate(900)),
                        UI::row()
                            ->gap(Unit::px(16))
                            ->alignMiddle()
                            ->content(
                                $this->counterButton('remove'),
                                $this->counterButton('add'),
                            ),
                    ),
            );
    }

    private function counterButton(string $icon): UIElement
    {
        return UI::button('')
            ->width(Unit::px(48))
            ->height(Unit::px(48))
            ->roundedFull()
            ->bordered()
            ->borderColor(Color::slate(200))
            ->background(Color::white())
            ->color(Color::slate(700))
            ->clickable()
            ->background(Color::orange(500), Pseudo::hover())
            ->color(Color::white(), Pseudo::hover())
            ->borderColor(Color::orange(500), Pseudo::hover())
            ->content((new Icon($icon))->size(20));
    }
}
