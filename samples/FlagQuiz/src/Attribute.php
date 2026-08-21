<?php

namespace Samples\FlagQuiz;

/**
 * The things a country is known by. Any of them can be shown, and any one of
 * them asked for, which is the whole game: a question is a set of these
 * pointed at another one (see {@see Quiz}).
 *
 * The first four are the ones every country has — its flag, its name, its
 * capital, its place on the map. The rest come out of {@see CountryFacts} and
 * are not universal: plenty of countries have no river worth naming and no
 * mountains at all, so an attribute also knows which countries can answer for
 * it ({@see hasData()}) and a round quietly narrows to those.
 *
 * Most of them are words and are answered by typing. Two are not — a flag is
 * picked out of a handful, a place is clicked on the map — and those are
 * answered by pointing, which is why an attribute knows how it is given as
 * well as what it is.
 *
 * String-backed so it round-trips through the session state.
 */
enum Attribute: string
{
    case Flag = 'flag';
    case Name = 'name';
    case Capital = 'capital';
    case Location = 'location';
    case Landmark = 'landmark';
    case River = 'river';
    case Mountains = 'mountains';
    case Lake = 'lake';
    case Population = 'population';

    /** Label in the source and destination lists. */
    public function label(): string
    {
        return match ($this) {
            self::Flag => 'Flag',
            self::Name => 'Country name',
            self::Capital => 'Capital city',
            self::Location => 'Place on the map',
            self::Landmark => 'Famous landmark',
            self::River => 'Major river',
            self::Mountains => 'Mountain range',
            self::Lake => 'Big lake or dam',
            self::Population => 'Population',
        };
    }

    /** Short form, for reading a pairing back in a line: "Flag → Capital". */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Flag => 'Flag',
            self::Name => 'Name',
            self::Capital => 'Capital',
            self::Location => 'Map',
            self::Landmark => 'Landmark',
            self::River => 'River',
            self::Mountains => 'Mountains',
            self::Lake => 'Lake',
            self::Population => 'Population',
        };
    }

    /** What showing this attribute gives the player. */
    public function sourceHint(): string
    {
        return match ($this) {
            self::Flag => 'The flag is shown',
            self::Name => 'The country is named',
            self::Capital => 'Its capital is named',
            self::Location => 'It is highlighted on the map',
            self::Landmark => 'Its best-known landmarks are named',
            self::River => 'Its major rivers are named',
            self::Mountains => 'Its mountain ranges are named',
            self::Lake => 'Its big lakes and dams are named',
            self::Population => 'Its population is given, in millions',
        };
    }

    /** How this attribute is answered when it's the one being asked for. */
    public function destinationHint(): string
    {
        return match ($this) {
            self::Flag => 'Pick it out of six flags',
            self::Name => 'Type the country name',
            self::Capital => 'Type the capital city',
            self::Location => 'Click the country on the map',
            self::Landmark => 'Name any one of its landmarks',
            self::River => 'Name any one of its rivers',
            self::Mountains => 'Name any one of its ranges',
            self::Lake => 'Name any one of its lakes or dams',
            self::Population => 'Type it in millions, to the nearest 0.1',
        };
    }

    /** Answered in words, at the keyboard — rather than by pointing at it. */
    public function isTyped(): bool
    {
        return $this !== self::Flag && $this !== self::Location;
    }

    /**
     * Whether this attribute names several things at once — a country has one
     * name but four landmarks. What it is shown as, and how much room that
     * needs on screen, both follow from it.
     */
    public function isList(): bool
    {
        return match ($this) {
            self::Landmark, self::River, self::Mountains, self::Lake => true,
            default => false,
        };
    }

    /**
     * Whether the value has to say what it is. A country's name and its
     * capital read as themselves; "Zambezi" and "12.4 million" do not, and
     * shown bare beside each other they would be a riddle about the question
     * rather than the question.
     */
    public function needsLabel(): bool
    {
        return $this !== self::Name && $this !== self::Capital;
    }

    /**
     * Whether this attribute comes out of {@see CountryFacts} — the ones a
     * country can simply be without, as against the four every country has.
     */
    public function isFact(): bool
    {
        return match ($this) {
            self::Flag, self::Name, self::Capital, self::Location => false,
            default => true,
        };
    }

    /**
     * The five facts, in list order. What Explore lays out for a country, and
     * the set anything choosing among them works from.
     *
     * @return self[]
     */
    public static function facts(): array
    {
        return array_values(array_filter(self::cases(), fn(self $a) => $a->isFact()));
    }

    /**
     * Whether this fact is somewhere on the map rather than merely true of the
     * country — a landmark stands in one spot, a river runs a course, a range
     * and a lake cover ground, and each can be drawn where it is. A population
     * cannot: it is a number about the whole country and has nowhere of its
     * own to be.
     */
    public function isPlace(): bool
    {
        return match ($this) {
            self::Landmark, self::River, self::Mountains, self::Lake => true,
            default => false,
        };
    }

    /**
     * Whether this attribute can be part of a question at all.
     *
     * All but the population. A population is a fact about a country and
     * nothing more: shown, it gives away which country you are looking at
     * before the question starts; asked for, it wants a number to the nearest
     * hundred thousand that nobody carries in their head. It stays in Explore,
     * where it is something to read, and stays out of the quiz, where it was
     * never a question worth asking.
     */
    public function isQuizzable(): bool
    {
        return $this !== self::Population;
    }

    /**
     * The attributes a question can be built from, in list order.
     *
     * @return self[]
     */
    public static function quizzable(): array
    {
        return array_values(array_filter(self::cases(), fn(self $a) => $a->isQuizzable()));
    }

    /**
     * The four a country is known by, which are the four the flags tab builds
     * questions from. They pair with each other freely, so that tab is the one
     * place a question is still assembled rather than picked.
     *
     * @return self[]
     */
    public static function identity(): array
    {
        return [self::Flag, self::Name, self::Capital, self::Location];
    }

    /** What a tab of these is called: the plural, since a tab is a subject. */
    public function tabLabel(): string
    {
        return match ($this) {
            self::Landmark => 'Landmarks',
            self::River => 'Rivers',
            self::Mountains => 'Mountains',
            self::Lake => 'Lakes & dams',
            default => $this->shortLabel(),
        };
    }

    /**
     * How the place reads as a position — "Landmark location", "River course".
     * A river is not at a point the way a landmark is, and a range covers
     * ground rather than sitting on it, so each says where it is in its own
     * terms rather than all of them borrowing the word "location".
     */
    public function locationLabel(): string
    {
        return match ($this) {
            self::Landmark => 'Landmark location',
            self::River => 'River course',
            self::Mountains => 'Range location',
            self::Lake => 'Lake location',
            default => $this->shortLabel(),
        };
    }

    /**
     * Whether these two can be in the same question.
     *
     * The four a country is known by — its flag, its name, its capital, its
     * place on the map — pair with each other freely: each identifies the same
     * single country, so any of them can ask after any other.
     *
     * The places do not. A landmark, a river, a range and a lake are things in
     * their own right, not properties of a country, and there are only two
     * questions worth asking about a thing that is somewhere: *there it is,
     * what is it called* and *here is its name, where is it*. Both of those
     * pair the place with the map, so the map is the only thing a place pairs
     * with — not the flag, not the country's name, and not another place.
     *
     * That leaves the map doing double duty, and deliberately: paired with a
     * country attribute it means the country's outline, and paired with a
     * place it means that place drawn where it is. Both are "where this is",
     * which is what the map has always meant here.
     */
    public static function compatible(self $a, self $b): bool
    {
        if ($a === $b) {
            return true;
        }
        if (!$a->isQuizzable() || !$b->isQuizzable()) {
            return false;
        }
        if ($a->isPlace() || $b->isPlace()) {
            return $a === self::Location || $b === self::Location;
        }
        return true;
    }

    /**
     * The facts that can be drawn on the map, in list order — the layers
     * Explore offers to switch on and off.
     *
     * @return self[]
     */
    public static function places(): array
    {
        return array_values(array_filter(self::cases(), fn(self $a) => $a->isPlace()));
    }

    /**
     * Everything Explore's map can draw, in list order: the countries
     * themselves, then the things standing on them.
     *
     * The countries are {@see Location} — "where a country is" *is* the layer
     * of country shapes, so it is the same attribute rather than a sixth thing
     * invented to sit beside it. Switching it off leaves the landmarks, the
     * rivers and the ranges on the bare basemap, which is a way of seeing the
     * geography without the borders drawn over it.
     *
     * @return self[]
     */
    public static function layers(): array
    {
        return [self::Location, ...self::places()];
    }

    /**
     * What a layer is called on its chip. Almost always the short label, but
     * {@see Location} is "Map" in a question ("Flag → Map") and that says
     * nothing as the name of a layer on a map; as a layer it is the countries.
     */
    public function layerLabel(): string
    {
        return $this === self::Location ? 'Countries' : $this->shortLabel();
    }

    /**
     * Whether this country can be asked about this attribute at all. The four
     * universal attributes always can; the facts only where the country
     * carries them, which is why a rivers round is a smaller round.
     */
    public function hasData(Country $country): bool
    {
        return match ($this) {
            self::Population => $country->facts()->hasPopulation(),
            default => !$this->isFact() || $this->valuesOf($country) !== [],
        };
    }

    /**
     * Whether the map can actually draw this country's places for this
     * attribute. Naming a place means seeing it and finding one means clicking
     * it, so both questions need geometry — a river the catalogue names but
     * Natural Earth has no line for cannot be shown or pointed at, and the
     * country drops out of the round rather than coming up unanswerable.
     */
    public function isDrawable(Country $country): bool
    {
        if (!$this->isPlace()) {
            return true;
        }
        foreach ($this->valuesOf($country) as $name) {
            $drawable = $this === self::Landmark
                ? LandmarkPlaces::for($country->code, $name) !== null
                : PlaceShapes::has($this, $country->code, $name);
            if ($drawable) {
                return true;
            }
        }
        return false;
    }

    /**
     * Every value of this attribute the country carries — one for most, several
     * for the facts and for the countries that split their capital. All of them
     * are shown, and any one of them is a right answer.
     *
     * @return string[]
     */
    public function valuesOf(Country $country): array
    {
        $facts = $country->facts();
        return match ($this) {
            self::Capital => $country->capitals,
            self::Landmark => $facts->landmarks,
            self::River => $facts->rivers,
            self::Mountains => $facts->mountains,
            self::Lake => $facts->lakes,
            self::Population => $facts->hasPopulation() ? [$facts->populationLabel()] : [],
            // The flag and the place aren't words, so they say themselves with
            // the country's name — the only way to put either in a sentence.
            default => [$country->name],
        };
    }

    /** This attribute of a country, as one readable line. */
    public function textOf(Country $country): string
    {
        return implode(' / ', $this->valuesOf($country));
    }

    /** Whether a typed answer is right for this country. */
    public function matches(Country $country, string $guess): bool
    {
        return match ($this) {
            self::Name => $country->matches($guess),
            self::Capital => $country->matchesCapital($guess),
            self::Population => $this->matchesPopulation($country, $guess),
            // Not typed at all: a flag and a place are answered by pointing,
            // and nothing typed can be right.
            self::Flag, self::Location => false,
            default => self::namesOneOf($this->valuesOf($country), $guess),
        };
    }

    /** Placeholder for the answer field. */
    public function placeholder(): string
    {
        return match ($this) {
            self::Capital => 'Type the capital…',
            self::Landmark => 'Type a landmark…',
            self::River => 'Type a river…',
            self::Mountains => 'Type a mountain range…',
            self::Lake => 'Type a lake or dam…',
            self::Population => 'Millions — e.g. 8.4',
            default => 'Type the country…',
        };
    }

    /**
     * The population, in millions, to the nearest 0.1 — which is the nearest
     * 100 000 people, and is what the question shows when it shows a
     * population, so what is asked for and what is accepted are one number.
     * Compared as whole 100 000s rather than as two floats hoping to land on
     * the same value.
     */
    private function matchesPopulation(Country $country, string $guess): bool
    {
        $facts = $country->facts();
        if (!$facts->hasPopulation()) {
            return false;
        }
        $millions = self::readMillions($guess);
        return $millions !== null
            && (int)round($millions * 10) === $facts->populationSteps();
    }

    /**
     * A population as typed, in millions. "8.4", "8,4", "8.4 million" and
     * "8.4m" are all the same answer — the unit is part of the question, so
     * writing it out again is not a different reply. Anything that isn't a
     * number after that is no answer at all.
     */
    private static function readMillions(string $guess): ?float
    {
        $text = mb_strtolower(trim($guess));
        $text = preg_replace('/\b(million|millions|mill|mio|m)\b|m$/', '', $text);
        // A comma is a decimal point in most of the world and a thousands
        // separator in the rest; here the number is small enough that only the
        // first reading can be meant.
        $text = str_replace([',', ' '], ['.', ''], (string)$text);
        return is_numeric($text) ? (float)$text : null;
    }

    /**
     * Whether the guess names any one of these places. Matched on the same
     * normalisation country names use, and again with the generic words a
     * place-name is topped and tailed with dropped — so "Everest" answers for
     * Mount Everest and "Kariba" for both Lake Kariba and the Kariba Dam,
     * which is how people say them.
     *
     * @param string[] $values
     */
    private static function namesOneOf(array $values, string $guess): bool
    {
        $needle = Country::normalize($guess);
        if ($needle === '') {
            return false;
        }
        $core = self::coreName($needle);
        foreach ($values as $value) {
            $normalized = Country::normalize($value);
            if ($needle === $normalized || $core === self::coreName($normalized)) {
                return true;
            }
        }
        return false;
    }

    /**
     * A normalised place-name with the words that classify it rather than name
     * it removed. Falls back to the whole name where nothing else is left —
     * a place called only "Lake" would otherwise match every lake there is.
     */
    private static function coreName(string $normalized): string
    {
        $generic = [
            'mount', 'mt', 'mountain', 'mountains', 'range', 'ranges', 'massif',
            'highlands', 'plateau', 'escarpment', 'lake', 'loch', 'lough',
            'reservoir', 'river', 'dam', 'sea', 'national', 'park',
        ];
        $words = array_filter(
            explode(' ', $normalized),
            fn(string $word) => $word !== '' && !in_array($word, $generic, true),
        );
        $core = implode(' ', $words);
        return $core === '' ? $normalized : $core;
    }
}
