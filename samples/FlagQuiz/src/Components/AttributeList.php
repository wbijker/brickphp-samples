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
use HeroIcons\HeroIcons;
use Samples\FlagQuiz\Attribute;
use Samples\FlagQuiz\Palette;

/**
 * One of the two lists that make up a question: the attributes to show, and
 * the one to ask for. Both are the same list of four with the same checkboxes;
 * they differ in how many may be ticked and in what each row says about
 * itself, which is what the caller passes in.
 *
 * A row that can't be ticked is still shown, greyed — on the sources list that
 * is the attribute being asked for, and leaving it out would make the list
 * jump about every time the question turned around.
 */
class AttributeList extends Component
{
    /**
     * @param Attribute[] $options  the rows this list offers, in order
     * @param Attribute[] $checked
     * @param Attribute[] $disabled rows shown but not selectable
     * @param Closure     $onToggle fn(Attribute $attribute): void
     * @param bool        $single   one tick only — draws round boxes rather than square
     */
    public function __construct(
        private string $title,
        private string $caption,
        private array $options,
        private array $checked,
        private array $disabled,
        private Closure $onToggle,
        private bool $single = false,
    ) {}

    protected function build(): VNode
    {
        $rows = [];
        foreach ($this->options as $attribute) {
            $rows[] = match (true) {
                in_array($attribute, $this->disabled, true) => $this->disabledRow($attribute),
                in_array($attribute, $this->checked, true) => $this->checkedRow($attribute),
                default => $this->row($attribute),
            };
        }

        return UI::column()
            ->width(Unit::full())
            ->gap(Unit::px(10))
            ->content(
                UI::row()
                    ->alignMiddle()
                    ->gap(Unit::px(8))
                    ->content(
                        UI::text($this->title)
                            ->fontSize(FontSize::ExtraSmall)->uppercase()->color(Palette::labelMuted()),
                        UI::text($this->caption)
                            ->fontSize(FontSize::ExtraSmall)->color(Palette::footerCount()),
                    ),
                UI::column()
                    ->width(Unit::full())
                    ->background(Palette::white())
                    ->bordered()
                    ->borderColor(Palette::border())
                    ->rounded(Unit::px(16))
                    ->clipContent()
                    ->content(...$rows),
            );
    }

    // The three row states are written as separate literal chains rather than
    // one chain with conditional colours: each is a different thing (a choice,
    // a made choice, a choice that isn't available), and reading them side by
    // side beats reading three ternaries.

    private function row(Attribute $attribute): UIElement
    {
        return UI::row()
            ->alignMiddle()
            ->gap(Unit::px(12))
            ->padding(x: Unit::px(16), y: Unit::px(13))
            ->clickable()
            ->onClick(fn() => ($this->onToggle)($attribute))
            ->content(
                $this->box(false),
                $this->labels($attribute, Palette::ink(), Palette::subtle()),
            );
    }

    private function checkedRow(Attribute $attribute): UIElement
    {
        return UI::row()
            ->alignMiddle()
            ->gap(Unit::px(12))
            ->background(Palette::blueWash())
            ->padding(x: Unit::px(16), y: Unit::px(13))
            ->clickable()
            ->onClick(fn() => ($this->onToggle)($attribute))
            ->content(
                $this->box(true),
                $this->labels($attribute, Palette::ink(), Palette::subtle()),
            );
    }

    /** Shown, but not on offer — no handler, so the row doesn't respond. */
    private function disabledRow(Attribute $attribute): UIElement
    {
        return UI::row()
            ->alignMiddle()
            ->gap(Unit::px(12))
            ->padding(x: Unit::px(16), y: Unit::px(13))
            ->content(
                $this->box(false),
                $this->labels($attribute, Palette::labelMuted(), Palette::labelMuted()),
            );
    }

    private function labels(Attribute $attribute, $title, $hint): UIElement
    {
        return UI::column()
            ->grow()
            ->gap(Unit::px(2))
            ->content(
                UI::text($attribute->label())
                    ->weight(FontWeight::SemiBold)->fontSize(FontSize::Small)->color($title),
                UI::text($this->single ? $attribute->destinationHint() : $attribute->sourceHint())
                    ->fontSize(FontSize::ExtraSmall)->color($hint),
            );
    }

    /**
     * The tick box. Round where only one may be chosen and square where several
     * may — the shape is the rule, and it is the only thing on screen saying
     * which list behaves which way.
     */
    private function box(bool $checked): UIElement
    {
        if ($checked && $this->single) {
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
        if ($checked) {
            // A drawn tick rather than a ✓ character: the box is a picture of
            // a state, and a letter in it would join the row's own words —
            // read out by anything that reads the page as text, copied along
            // with them, sized by the font rather than by the box.
            return UI::row()
                ->noShrink()
                ->alignCenter()
                ->alignMiddle()
                ->width(Unit::px(20))
                ->height(Unit::px(20))
                ->rounded(Unit::px(6))
                ->background(Palette::blue())
                ->bordered(2)
                ->borderColor(Palette::blue())
                ->color(Palette::white())
                ->content(
                    HeroIcons::Check('none', 3, 'currentColor', '')
                        ->attr('width', '13')
                        ->attr('height', '13'),
                );
        }
        if ($this->single) {
            return UI::container()
                ->noShrink()
                ->width(Unit::px(20))
                ->height(Unit::px(20))
                ->roundedFull()
                ->background(Palette::white())
                ->bordered(2)
                ->borderColor(Palette::border());
        }
        return UI::container()
            ->noShrink()
            ->width(Unit::px(20))
            ->height(Unit::px(20))
            ->rounded(Unit::px(6))
            ->background(Palette::white())
            ->bordered(2)
            ->borderColor(Palette::border());
    }
}
