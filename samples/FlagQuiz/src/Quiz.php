<?php

namespace Samples\FlagQuiz;

/**
 * A question, in the abstract: the attributes shown, and the one asked for.
 * Every game mode the quiz has is one of these — twelve of them if you count
 * a single source pointed at each of the other three attributes, and more once
 * sources are combined (flag *and* capital → name is a different question from
 * either alone).
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

    /** How a wrong answer is recorded, which decides how it reads back. */
    public function guessKind(): GuessKind
    {
        return $this->isTyped() ? GuessKind::Typed : GuessKind::Picked;
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
        return match ($this->destination) {
            Attribute::Flag => 'Which flag is this?',
            Attribute::Name => 'Which country is this?',
            Attribute::Capital => 'What is its capital city?',
            Attribute::Location => 'Where is this country?',
        };
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
