<?php

namespace Samples\FlagQuiz\Screens;

use Closure;
use BrickPHP\Events\InputEvent;
use BrickPHP\UI\Direction;
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
use Samples\FlagQuiz\Components\PlaceQuestionList;
use Samples\FlagQuiz\Components\FlagGlobe;
use Samples\FlagQuiz\Components\Tab;
use Samples\FlagQuiz\Components\TabView;
use Samples\FlagQuiz\Components\Toggle;
use Samples\FlagQuiz\Continent;
use Samples\FlagQuiz\FlagSort;
use Samples\FlagQuiz\Palette;
use Samples\FlagQuiz\PlaceQuestion;
use Samples\FlagQuiz\Quiz;

/**
 * The landing screen: a header of title and flag globe, the two lists that
 * spell out the question, the continent filter and the settings, with Start at
 * the bottom. The lists are driven straight off {@see Attribute} and the
 * continent chips off {@see Continent}, which carry their own copy.
 */
class StartScreen extends Component
{
    /** The tab that isn't a place — the one the question is built on. */
    private const FLAGS_TAB = 'flags';

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
     * @param Closure $onSelectTab           fn(string $key): void — 'flags' or a place's value
     * @param Closure $onSelectPlaceQuestion fn(Attribute $place, PlaceQuestion $q): void
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
        private Closure $onSelectTab,
        private Closure $onSelectPlaceQuestion,
    ) {}

    protected function build(): VNode
    {
        return $this->page();
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
            ->gap(Unit::px(30))
            ->padding(Unit::px(24))
            ->padding(Unit::px(40), Pseudo::sm())
            ->content(
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
                    ->gap(Unit::px(22))
                    ->background(Palette::white())
                    ->bordered()
                    ->borderColor(Palette::border())
                    ->rounded(Unit::px(20))
                    ->padding(Unit::px(24))
                    ->padding(Unit::px(28), Pseudo::sm())
                    ->shadow(Shadow::Large)
                    ->content(
                        $this->heading(),
                        $this->body(),
                    ),
            );
    }

    /**
     * The header: the name of the game, and the game itself standing beside it
     * — every flag on a globe, on the right from lg and above the words when
     * there is no room for that. The globe is the mark now; a logo next to it
     * would be a second one.
     */
    private function heading(): UIElement
    {
        return UI::column()
            ->width(Unit::full())
            ->direction(Direction::rowReverse(), Pseudo::lg())
            ->alignCenter()
            ->alignMiddle()
            ->gap(Unit::px(16))
            ->gap(Unit::px(40), Pseudo::lg())
            ->content(
                new FlagGlobe(),
                UI::column()
                    ->alignCenter()
                    ->gap(Unit::px(6))
                    ->content(
                        UI::text('Vexi')
                            ->fontSize(FontSize::FourXL)->fontSize(FontSize::FiveXL, Pseudo::sm())
                            ->weight(FontWeight::SemiBold)->center(),
                        UI::text('Learn the flags and countries of the world.')
                            ->center()->fontSize(FontSize::Base)->color(Palette::subtle()),
                    ),
            );
    }

    /** Everything you actually choose. */
    private function body(): UIElement
    {
        return UI::column()
            ->width(Unit::full())
            ->alignCenter()
            ->gap(Unit::px(22))
            ->content(
                $this->questionBuilder(),
                // The two supporting choices — which countries, and how
                // strictly — pair off into columns once there is width for
                // them, and stack again when there isn't.
                UI::grid(1)
                    ->columns(2, Pseudo::lg())
                    ->width(Unit::full())
                    ->gap(Unit::px(24))
                    ->alignItems(GridAlign::Start)
                    ->content(
                        $this->continentPicker(),
                        $this->settings(),
                    ),
                ...$this->startAction(),
            );
    }

    /**
     * Start, and — when there is nothing to start — the reason instead of it.
     * A question can name a fact that no country in the chosen continents
     * carries (rivers in Oceania, say), and a button that starts a game with
     * no questions in it would only look broken.
     *
     * @return UIElement[]
     */
    private function startAction(): array
    {
        if ($this->count === 0) {
            return [
                UI::text('No country in the chosen continents has all of that — ask for something else, or add a continent.')
                    ->center()
                    ->fontSize(FontSize::Small)
                    ->color(Palette::subtle()),
                UI::container()
                    ->width(Unit::full())
                    ->background(Palette::border())
                    ->color(Palette::labelMuted())
                    ->rounded(Unit::px(14))
                    ->padding(Unit::px(17))
                    ->content(
                        UI::text('Start Quiz')
                            ->center()
                            ->weight(FontWeight::SemiBold)
                            ->fontSize(FontSize::Large),
                    ),
            ];
        }

        return [
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
        ];
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
                new TabView(
                    $this->tabs(),
                    $this->quiz->place()?->value ?? self::FLAGS_TAB,
                    $this->onSelectTab,
                    $this->quiz->place() === null
                        ? $this->flagsTab()
                        : $this->placeTab($this->quiz->place()),
                ),
            );
    }

    /**
     * A tab for the flags and one for each kind of place. Read off the
     * question rather than stored beside it: a question is about a place or it
     * is not, and if it is, it is about exactly one — so which tab is open is
     * never in doubt and can never disagree with what is about to be played.
     *
     * @return Tab[]
     */
    private function tabs(): array
    {
        $tabs = [new Tab(self::FLAGS_TAB, 'Flags')];
        foreach (Attribute::places() as $place) {
            $tabs[] = new Tab($place->value, $place->tabLabel());
        }
        return $tabs;
    }

    /**
     * The flags tab: the two lists, offering the four attributes a country is
     * known by. Those four pair with each other freely, so nothing here is
     * ever greyed for being an impossible pairing — the only greyed row is the
     * one being asked for, which cannot also be shown.
     */
    private function flagsTab(): VNode
    {
        return UI::grid(1)
            ->columns(2, Pseudo::sm())
            ->gap(Unit::px(16))
            ->alignItems(GridAlign::Start)
            ->content(
                new AttributeList(
                    'Show me',
                    'one or more',
                    Attribute::identity(),
                    $this->quiz->sources,
                    [$this->quiz->destination],
                    $this->onToggleSource,
                ),
                new AttributeList(
                    'Ask me for',
                    'one',
                    Attribute::identity(),
                    [$this->quiz->destination],
                    [],
                    $this->onSelectDestination,
                    single: true,
                ),
            );
    }

    /** A place tab: the two questions there are to ask about it, one ticked. */
    private function placeTab(Attribute $place): VNode
    {
        return new PlaceQuestionList(
            $place,
            PlaceQuestion::of($this->quiz) ?? PlaceQuestion::Name,
            fn(PlaceQuestion $question) => ($this->onSelectPlaceQuestion)($place, $question),
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
                        // Not simply how many countries the chosen continents
                        // hold: a question naming a river or a mountain range
                        // is only asked of the countries that have one, so
                        // this is what the round would actually be played over.
                        UI::text($this->count . ' countries in play')
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
