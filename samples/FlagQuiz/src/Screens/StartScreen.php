<?php

namespace Samples\FlagQuiz\Screens;

use Closure;
use BrickPHP\Events\InputEvent;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\GridAlign;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use Samples\FlagQuiz\Attribute;
use Samples\FlagQuiz\Components\AttributeList;
use Samples\FlagQuiz\Components\Toggle;
use Samples\FlagQuiz\Continent;
use Samples\FlagQuiz\FlagSort;
use Samples\FlagQuiz\Logo;
use Samples\FlagQuiz\Palette;
use Samples\FlagQuiz\Quiz;

/**
 * The landing screen: logo, title, the two lists that spell out the question,
 * the continent filter and the settings, with Start at the bottom. The lists
 * are driven straight off {@see Attribute} and the continent chips off
 * {@see Continent}, which carry their own copy.
 */
class StartScreen extends Component
{
    /**
     * @param Quiz        $quiz              the question being built: shown → asked for
     * @param Continent[] $continents        currently selected continents
     * @param Closure $onStart              fn(): void
     * @param Closure $onToggleSource       fn(Attribute $attribute): void
     * @param Closure $onSelectDestination  fn(Attribute $attribute): void
     * @param Closure $onToggleShowFlags    fn(): void
     * @param Closure $onToggleStrict       fn(): void
     * @param Closure $onSelectSort         fn(FlagSort $sort): void
     * @param Closure $onToggleContinent    fn(Continent $continent): void
     * @param Closure $onExplore            fn(): void — launch Explore directly
     */
    public function __construct(
        private int $count,
        private Quiz $quiz,
        private bool $showFlags,
        private bool $strict,
        private FlagSort $flagSort,
        private array $continents,
        private Closure $onStart,
        private Closure $onToggleSource,
        private Closure $onSelectDestination,
        private Closure $onToggleShowFlags,
        private Closure $onToggleStrict,
        private Closure $onSelectSort,
        private Closure $onToggleContinent,
        private Closure $onExplore,
    ) {}

    protected function build(): VNode
    {
        // Backdrop + content as two layers: the flag field is a real image
        // element behind the stack (added before primary(), so it paints
        // underneath), cropped to fill whatever shape the screen is. The
        // scrolling column is the primary layer and sizes the stack.
        return UI::layers()
            ->grow()
            ->minHeight(Unit::em(0))
            ->layer(
                UI::image('/assets/images/flags-background-soft.png')
                    ->width(Unit::full())
                    ->height(Unit::full())
                    ->objectCover()
                    ->objectCenter(),
            )
            ->primary($this->page());
    }

    /**
     * One single scrolling column: every section is just a stacked row, the
     * Start button included. No pinned footer or vertical centering layered
     * over the content. Roomier padding from the sm breakpoint up.
     */
    private function page(): UIElement
    {
        return UI::column()
            ->grow()
            ->minHeight(Unit::em(0))
            ->height(Unit::full())
            ->scrollableY()
            ->alignCenter()
            ->padding(Unit::px(24))
            ->padding(Unit::px(40), Pseudo::sm())
            // Positioned, so it stacks above the backdrop layer rather than
            // under it — an absolute sibling paints over a static one.
            ->relative()
            ->content(
                // The choices sit on their own white panel: over a field of
                // flags, headline and labels need a plain ground of their own
                // to read against, and the panel edge is what separates the
                // thing you interact with from the picture behind it.
                //
                // The panel takes the width it is given, in steps: a phone
                // column, a wider card on a tablet, and a broad one on a
                // desktop — where the sections below then sit side by side
                // rather than stacking into a scroll. Only the ceiling moves;
                // the panel is always centred and never fills a wide screen
                // edge to edge, which would leave the eye nowhere to land.
                UI::column()
                    ->width(Unit::full())
                    ->maxWidth(Unit::px(460))
                    ->maxWidth(Unit::px(760), Pseudo::sm())
                    ->maxWidth(Unit::px(1080), Pseudo::lg())
                    ->alignCenter()
                    ->gap(Unit::px(24))
                    ->background(Palette::white())
                    ->bordered()
                    ->borderColor(Palette::border())
                    ->rounded(Unit::px(20))
                    ->padding(Unit::px(24))
                    ->padding(Unit::px(32), Pseudo::sm())
                    ->shadow(Shadow::Large)
                    ->content(
                        UI::column()
                            ->alignCenter()
                            ->gap(Unit::px(12))
                            ->content(
                                new Logo(true),
                                UI::text('Vexi')
                                    ->fontSize(FontSize::FourXL)->fontSize(FontSize::FiveXL, Pseudo::sm())
                                    ->weight(FontWeight::SemiBold)->center(),
                                UI::text('Learn the flags and countries of the world.')
                                    ->center()->fontSize(FontSize::Base)->color(Palette::subtle()),
                            ),
                        $this->questionBuilder(),
                        // The two supporting choices — which countries, and
                        // how strictly — pair off into columns once there is
                        // width for them, and stack again when there isn't.
                        UI::grid(1)
                            ->columns(2, Pseudo::lg())
                            ->width(Unit::full())
                            ->gap(Unit::px(24))
                            ->alignItems(GridAlign::Start)
                            ->content(
                                $this->continentPicker(),
                                $this->settings(),
                            ),
                        UI::button('Start Quiz')
                            ->width(Unit::full())
                            ->background(Palette::ink())
                            ->color(Palette::white())
                            ->borderNone()
                            ->rounded(Unit::px(14))
                            ->padding(Unit::px(17))
                            ->weight(FontWeight::SemiBold)
                            ->fontSize(FontSize::Large)
                            ->shadow(Shadow::Large)
                            ->clickable()
                            ->onClick(fn() => ($this->onStart)()),
                    )
            );
    }

    /**
     * The question, built as two lists: everything to show on the left, the one
     * thing to ask for on the right. Between them they say the whole game —
     * tick the flag and ask for the capital, or tick the capital and the map
     * and ask for the name — so there is nothing else to choose a mode with.
     */
    private function questionBuilder(): UIElement
    {
        return UI::column()
            ->width(Unit::full())
            ->gap(Unit::px(10))
            ->content(
                // Header: what the two lists currently spell out, and the
                // Explore shortcut (a single click jumps straight into it).
                UI::row()
                    ->alignMiddle()
                    ->alignBetween()
                    ->wrap()
                    ->gap(Unit::px(8))
                    ->content(
                        UI::row()->alignMiddle()->gap(Unit::px(8))->content(
                            UI::text('The question')
                                ->fontSize(FontSize::ExtraSmall)->uppercase()->color(Palette::labelMuted()),
                            UI::text($this->quiz->summary())
                                ->fontSize(FontSize::ExtraSmall)->weight(FontWeight::SemiBold)->color(Palette::blue()),
                        ),
                        UI::button('Explore the map →')
                            ->noShrink()
                            ->borderNone()
                            ->background(Palette::transparent())
                            ->color(Palette::blue())
                            ->weight(FontWeight::SemiBold)
                            ->fontSize(FontSize::Small)
                            ->padding(Unit::none())
                            ->clickable()
                            ->onClick(fn() => ($this->onExplore)()),
                    ),
                UI::grid(1)
                    ->columns(2, Pseudo::sm())
                    ->gap(Unit::px(16))
                    ->alignItems(GridAlign::Start)
                    ->content(
                        new AttributeList(
                            'Show me',
                            'one or more',
                            $this->quiz->sources,
                            [$this->quiz->destination],
                            $this->onToggleSource,
                        ),
                        new AttributeList(
                            'Ask me for',
                            'one',
                            [$this->quiz->destination],
                            [],
                            $this->onSelectDestination,
                            single: true,
                        ),
                    ),
            );
    }

    private function continentPicker(): UIElement
    {
        $chips = [];
        foreach (Continent::cases() as $continent) {
            $chips[] = in_array($continent, $this->continents, true)
                ? $this->continentChipSelected($continent)
                : $this->continentChip($continent);
        }

        return UI::column()
            ->width(Unit::full())
            ->gap(Unit::px(10))
            ->content(
                UI::row()
                    ->alignMiddle()
                    ->gap(Unit::px(8))
                    ->content(
                        UI::text('Continents')
                            ->fontSize(FontSize::ExtraSmall)->uppercase()->color(Palette::labelMuted()),
                        UI::text($this->count . ' countries')
                            ->fontSize(FontSize::ExtraSmall)->color(Palette::footerCount()),
                    ),
                UI::row()->wrap()->gap(Unit::px(8))->content(...$chips),
            );
    }

    // The two chip states are written as separate literal chains so the
    // CssExtractor harvests both the selected (blue fill) and unselected
    // (outline) class sets — a ternary would only emit one branch.
    private function continentChip(Continent $continent): UIElement
    {
        return UI::row()
            ->noShrink()
            ->alignMiddle()
            ->background(Palette::white())
            ->bordered()
            ->borderColor(Palette::border())
            ->roundedFull()
            ->padding(x: Unit::px(14), y: Unit::px(8))
            ->clickable()
            ->onClick(fn() => ($this->onToggleContinent)($continent))
            ->content(
                UI::text($continent->label())
                    ->fontSize(FontSize::Small)->weight(FontWeight::Medium)->color(Palette::subtle()),
            );
    }

    private function continentChipSelected(Continent $continent): UIElement
    {
        return UI::row()
            ->noShrink()
            ->alignMiddle()
            ->background(Palette::blue())
            ->bordered()
            ->borderColor(Palette::blue())
            ->roundedFull()
            ->padding(x: Unit::px(14), y: Unit::px(8))
            ->clickable()
            ->onClick(fn() => ($this->onToggleContinent)($continent))
            ->content(
                UI::text($continent->label())
                    ->fontSize(FontSize::Small)->weight(FontWeight::SemiBold)->color(Palette::white()),
            );
    }

    private function settings(): UIElement
    {
        // The navigation setting means different things for different
        // questions and nothing at all for some of them (see
        // Quiz::navSetting) — where it means nothing, it and its divider are
        // left out rather than shown doing nothing.
        $rows = [];
        $navigation = $this->quiz->navSetting();
        if ($navigation !== null) {
            $rows[] = new Toggle(
                $navigation->label,
                $navigation->description,
                $this->showFlags,
                $this->onToggleShowFlags,
            );
            $rows[] = UI::container()->extendX()->height(Unit::em(0.0625))->background(Palette::border());
        }
        $rows[] = new Toggle(
            'Strict mode',
            'One guess per question — a wrong answer is final',
            $this->strict,
            $this->onToggleStrict,
        );
        $rows[] = UI::container()->extendX()->height(Unit::em(0.0625))->background(Palette::border());
        $rows[] = $this->flagOrderSetting();

        return UI::column()
            ->width(Unit::full())
            ->background(Palette::white())
            ->bordered()
            ->borderColor(Palette::border())
            ->rounded(Unit::px(16))
            ->clipContent()
            ->content(...$rows);
    }

    /**
     * The flag-order dropdown: orders the deck so visually similar flags sit next
     * to each other (by colour, by shape, or both) instead of being shuffled.
     */
    private function flagOrderSetting(): UIElement
    {
        $options = [];
        foreach (FlagSort::forQuiz($this->quiz) as $sort) {
            $options[] = UI::option($sort->label(), $sort->value)->selected($sort === $this->flagSort);
        }

        return UI::row()
            ->alignMiddle()
            ->alignBetween()
            ->gap(Unit::px(16))
            ->padding(x: Unit::px(20), y: Unit::px(16))
            ->content(
                UI::column()
                    ->grow()
                    ->gap(Unit::px(2))
                    ->content(
                        UI::text('Flag order')->weight(FontWeight::SemiBold)->fontSize(FontSize::Small),
                        UI::text('Group similar-looking flags together')
                            ->fontSize(FontSize::ExtraSmall)->color(Palette::subtle()),
                    ),
                UI::select()
                    ->noShrink()
                    ->background(Palette::white())
                    ->color(Palette::ink())
                    ->bordered()
                    ->borderColor(Palette::border())
                    ->rounded(Unit::px(10))
                    ->padding(x: Unit::px(12), y: Unit::px(8))
                    ->fontSize(FontSize::Small)
                    ->weight(FontWeight::Medium)
                    ->clickable()
                    ->onChange(fn(InputEvent $e) => ($this->onSelectSort)(FlagSort::from($e->value ?? 'random')))
                    ->options(...$options),
            );
    }
}
