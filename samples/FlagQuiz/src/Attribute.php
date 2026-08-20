<?php

namespace Samples\FlagQuiz;

/**
 * The four things a country is known by. Any of them can be shown, and any one
 * of them asked for, which is the whole game: a question is a set of these
 * pointed at another one (see {@see Quiz}).
 *
 * Two of them are words and are answered by typing. Two are not — a flag is
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

    /** Label in the source and destination lists. */
    public function label(): string
    {
        return match ($this) {
            self::Flag => 'Flag',
            self::Name => 'Country name',
            self::Capital => 'Capital city',
            self::Location => 'Place on the map',
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
        };
    }

    /** Answered in words, at the keyboard — rather than by pointing at it. */
    public function isTyped(): bool
    {
        return $this === self::Name || $this === self::Capital;
    }

    /**
     * This attribute of a country, as text. The flag and the place aren't
     * words, so they answer with the country's name — the only way to say
     * either of them in a sentence.
     */
    public function textOf(Country $country): string
    {
        return match ($this) {
            self::Capital => $country->capitalLabel(),
            default => $country->name,
        };
    }

    /** Whether a typed answer is right for this country. */
    public function matches(Country $country, string $guess): bool
    {
        return match ($this) {
            self::Name => $country->matches($guess),
            self::Capital => $country->matchesCapital($guess),
            // Not typed at all: a flag and a place are answered by pointing,
            // and nothing typed can be right.
            default => false,
        };
    }

    /** Placeholder for the answer field. */
    public function placeholder(): string
    {
        return $this === self::Capital ? 'Type the capital…' : 'Type the country…';
    }
}
