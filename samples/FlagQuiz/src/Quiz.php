<?php

namespace Samples\FlagQuiz;

/**
 * A question, in the abstract: the attributes shown, and the one asked for.
 * Every game mode the quiz has is one of these — seventy-two of them if you
 * count a single source pointed at each of the other eight {@see Attribute}s,
 * and far more once sources are combined (flag *and* capital → name is a
 * different question from either alone).
 *
 * The pairing decides how the screen is built and how an answer is taken, so
 * those questions are answered here rather than by each screen picking the
 * state apart for itself. Built per render from the quiz's state; not stored.
 */
final class Quiz
{
    /** @param Attribute[] $sources what the player is shown (never empty, never the destination) */
    public function __construct(
        public readonly array $sources,
        public readonly Attribute $destination,
    ) {}

    public function shows(Attribute $attribute): bool
    {
        return in_array($attribute, $this->sources, true);
    }

    /** Whether the world map is on screen at all — as the question or the answer. */
    public function usesMap(): bool
    {
        return $this->shows(Attribute::Location) || $this->destination === Attribute::Location;
    }

    /** The map is where the answer is given, so nothing on it may be highlighted. */
    public function mapIsAnswer(): bool
    {
        return $this->destination === Attribute::Location;
    }

    /** The answer is one of a handful of flags. */
    public function picksFlag(): bool
    {
        return $this->destination === Attribute::Flag;
    }

    /** The answer is typed into the field. */
    public function isTyped(): bool
    {
        return $this->destination->isTyped();
    }

    /** The sources that are shown as content rather than by the map. */
    public function contentSources(): array
    {
        return array_values(array_filter($this->sources, fn(Attribute $a) => $a !== Attribute::Location));
    }

    /** The pairing in a line: "Flag + Capital → Map". */
    public function summary(): string
    {
        $shown = array_map(fn(Attribute $a) => $a->shortLabel(), $this->sources);
        return implode(' + ', $shown) . ' → ' . $this->destination->shortLabel();
    }

    /** The question, in words, above the answer area. */
    public function question(): string
    {
        // A place asked for is a place drawn on the map with nothing naming
        // it, and a place shown is a name to go and find. Both read as
        // questions about the thing itself rather than about its country,
        // because that is what they are.
        if ($this->locatesPlace()) {
            return match ($this->place()) {
                Attribute::Landmark => 'Where is this landmark?',
                Attribute::River => 'Where does this river run?',
                Attribute::Mountains => 'Where is this range?',
                default => 'Where is this lake?',
            };
        }

        return match ($this->destination) {
            Attribute::Flag => 'Which flag is this?',
            Attribute::Name => 'Which country is this?',
            Attribute::Capital => 'What is its capital city?',
            Attribute::Location => 'Where is this country?',
            Attribute::Landmark => 'Which landmark is shown?',
            Attribute::River => 'Which river is drawn?',
            Attribute::Mountains => 'Which range is shaded?',
            Attribute::Lake => 'Which lake is shaded?',
            Attribute::Population => 'How many people live there, in millions?',
        };
    }

    /**
     * Everything this question touches — what is shown and what is asked for.
     *
     * @return Attribute[]
     */
    public function attributes(): array
    {
        return [...$this->sources, $this->destination];
    }

    /**
     * Whether this attribute could join the question — nothing already in it
     * rules the pairing out. See {@see Attribute::compatible()}: a river and a
     * country name cannot be two halves of one question.
     */
    public function accepts(Attribute $attribute): bool
    {
        foreach ($this->attributes() as $chosen) {
            if (!Attribute::compatible($chosen, $attribute)) {
                return false;
            }
        }
        return true;
    }

    /**
     * What the "show me" list greys: whatever cannot be shown alongside the
     * thing being asked for. Ask for a river and the flag, the name, the
     * capital and the map go grey, because none of them is what a river
     * belongs to.
     *
     * @return Attribute[]
     */
    public function excludedSources(): array
    {
        return self::incompatibleWith([$this->destination]);
    }

    /**
     * Everything that clashes with any of these. Greyed rather than dropped so
     * the lists keep their shape — a row vanishing as you tick another one is
     * a list that moves under the pointer.
     *
     * @param Attribute[] $chosen
     * @return Attribute[]
     */
    private static function incompatibleWith(array $chosen): array
    {
        return array_values(array_filter(
            Attribute::quizzable(),
            function (Attribute $a) use ($chosen) {
                foreach ($chosen as $other) {
                    if (!Attribute::compatible($a, $other)) {
                        return true;
                    }
                }
                return false;
            },
        ));
    }

    /**
     * The place this question is about, if it is about one — the landmark, the
     * river, the range or the lake. Never more than one: a place pairs only
     * with the map ({@see Attribute::compatible()}), so a question holds at
     * most a single place and the map.
     */
    public function place(): ?Attribute
    {
        foreach ($this->attributes() as $attribute) {
            if ($attribute->isPlace()) {
                return $attribute;
            }
        }
        return null;
    }

    /**
     * "There it is — what is it called?" The place is drawn on the map, with
     * nothing naming it, and the answer is typed.
     */
    public function namesPlace(): bool
    {
        return $this->destination->isPlace();
    }

    /**
     * "Here is its name — where is it?" The place is named and the answer is a
     * click on the map, judged against where the place actually is rather than
     * against the country it happens to fall in.
     */
    public function locatesPlace(): bool
    {
        return $this->destination === Attribute::Location && $this->place() !== null;
    }

    /**
     * The places the map should draw, which is the one in the question — but
     * only when it is not the answer. Asked to find the Nile, drawing the Nile
     * is drawing the answer.
     *
     * @return Attribute[]
     */
    public function mappableFacts(): array
    {
        $place = $this->place();
        return $place !== null && !$this->locatesPlace() ? [$place] : [];
    }

    /**
     * Whether this country can be asked this question. Most of the facts are
     * not universal, so a question naming one is only a question for the
     * countries that carry it: ask for a river and the landlocked deserts and
     * the small islands step out of the round rather than come up unanswerable.
     */
    public function covers(Country $country): bool
    {
        foreach ($this->attributes() as $attribute) {
            if (!$attribute->hasData($country)) {
                return false;
            }
            // A place question is asked on the map, so the place has to be
            // something the map can draw as well as something the country has.
            if ($attribute->isPlace() && !$attribute->isDrawable($country)) {
                return false;
            }
        }
        return true;
    }

    /**
     * The navigation setting that applies here, or null when none does. Both
     * of them are ways of moving between questions rather than answering one,
     * so each needs its own thing on screen to move around in: the map when
     * the map is the question, the flag deck when the flags are.
     */
    public function navSetting(): ?NavSetting
    {
        if (!$this->isTyped()) {
            // Pointing at the answer already means clicking the very thing you
            // would otherwise navigate with.
            return null;
        }
        if ($this->shows(Attribute::Location)) {
            return new NavSetting('Free navigation', 'Tap any country on the map to jump between them');
        }
        if ($this->shows(Attribute::Flag)) {
            return new NavSetting('Show available flags', 'List the flags you have not answered yet');
        }
        return null;
    }
}
