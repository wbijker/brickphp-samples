<?php

namespace Samples\FlagQuiz\Components;

use Closure;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use Samples\FlagQuiz\Attribute;
use Samples\FlagQuiz\Palette;
use Samples\FlagQuiz\PlaceQuestion;

/**
 * A place tab's whole choice: the two questions there are to ask about a
 * landmark, a river, a range or a lake, one of them ticked.
 *
 * Two rows and no builder, because there is nothing to build. A place pairs
 * only with the map ({@see Attribute::compatible()}), so the question is
 * settled once you say which way round it runs — and a pair of round boxes
 * says that better than two lists with everything but the map greyed out.
 */
class PlaceQuestionList extends Component
{
    /**
     * @param Attribute     $place    the tab's subject
     * @param PlaceQuestion $chosen   which way round the question runs
     * @param Closure       $onSelect fn(PlaceQuestion $question): void
     */
    public function __construct(
        private Attribute $place,
        private PlaceQuestion $chosen,
        private Closure $onSelect,
    ) {}

    protected function build(): VNode
    {
        $rows = [];
        foreach (PlaceQuestion::cases() as $question) {
            $rows[] = $question === $this->chosen ? $this->chosenRow($question) : $this->row($question);
        }

        // No heading over the rows. The tab already names the subject and the
        // line above the tabs already reads the question back; a third "THE
        // QUESTION" between them would be the same words for the third time.
        return UI::column()
            ->width(Unit::full())
            ->background(Palette::white())
            ->bordered()
            ->borderColor(Palette::border())
            ->rounded(Unit::px(16))
            ->clipContent()
            ->content(...$rows);
    }

    // Written as two literal chains rather than one with the colours swapped
    // by a ternary — a made choice and an offered one are different things to
    // look at, and the same way the attribute rows are built.

    private function row(PlaceQuestion $question): UIElement
    {
        return UI::row()
            ->alignMiddle()
            ->gap(Unit::px(12))
            ->padding(x: Unit::px(16), y: Unit::px(13))
            ->clickable()
            ->onClick(fn() => ($this->onSelect)($question))
            ->content($this->box(false), $this->labels($question));
    }

    private function chosenRow(PlaceQuestion $question): UIElement
    {
        return UI::row()
            ->alignMiddle()
            ->gap(Unit::px(12))
            ->background(Palette::blueWash())
            ->padding(x: Unit::px(16), y: Unit::px(13))
            ->clickable()
            ->onClick(fn() => ($this->onSelect)($question))
            ->content($this->box(true), $this->labels($question));
    }

    private function labels(PlaceQuestion $question): UIElement
    {
        return UI::column()
            ->grow()
            ->gap(Unit::px(2))
            ->content(
                UI::text($question->label($this->place))
                    ->weight(FontWeight::SemiBold)->fontSize(FontSize::Small)->color(Palette::ink()),
                UI::text($question->hint())
                    ->fontSize(FontSize::ExtraSmall)->color(Palette::subtle()),
            );
    }

    /** Round, because only one of the two can be chosen — the same shape the
     *  "ask me for" list uses to say the same thing. */
    private function box(bool $checked): UIElement
    {
        if ($checked) {
            return UI::row()
                ->noShrink()
                ->alignCenter()
                ->alignMiddle()
                ->width(Unit::px(20))
                ->height(Unit::px(20))
                ->roundedFull()
                ->background(Palette::blue())
                ->bordered(2)
                ->borderColor(Palette::blue())
                ->content(
                    UI::container()
                        ->width(Unit::px(7))
                        ->height(Unit::px(7))
                        ->roundedFull()
                        ->background(Palette::white()),
                );
        }
        return UI::container()
            ->noShrink()
            ->width(Unit::px(20))
            ->height(Unit::px(20))
            ->roundedFull()
            ->background(Palette::white())
            ->bordered(2)
            ->borderColor(Palette::border());
    }
}
