<?php

namespace Samples\FlagQuiz\Components;

use Closure;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use Samples\FlagQuiz\Country;

/**
 * Flags mode guess area: the current flag above the shared {@see GuessInput}.
 */
class GuessPanel extends Component
{
    public function __construct(
        private Country $current,
        private bool $wrong,
        private Closure $onGuess,
        private Closure $onSkip,
        private Closure $onNext,
    ) {}

    protected function build(): VNode
    {
        return UI::column()
            ->grow()
            ->minHeight(Unit::px(0))
            ->alignMiddle()
            ->content(
                $this->buildFlag(),
                new GuessInput(
                    $this->current->code,
                    $this->wrong,
                    $this->onGuess,
                    $this->onSkip,
                    $this->onNext,
                ),
            );
    }

    private function buildFlag(): UIElement
    {
        return UI::row()
            ->extendX()
            ->height(Unit::px(210))
            ->height(Unit::px(288), Pseudo::lg())
            ->alignCenter()
            ->alignMiddle()
            ->padding(Unit::px(24))
            ->padding(Unit::px(36), Pseudo::sm())
            ->content(
                UI::image($this->current->bigUrl(), $this->current->name)
                    ->maxWidth(Unit::full())
                    ->maxHeight(Unit::full())
                    ->objectContain()
                    ->rounded(Unit::px(6))
                    ->shadow(Shadow::Small)
            );
    }
}
