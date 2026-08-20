<?php

namespace Samples\FlagQuiz;

/**
 * The four ways to play. Each case carries its own start-screen copy and the
 * mode-specific wording of the navigation setting, so the screens stay free of
 * magic strings. Persisted as session state (see {@see GamePhase}).
 *
 * The two map quizzes run the same question the two ways round: Locations
 * highlights a country and asks for its name, Pinpoint gives the flag and the
 * name and asks where it is.
 */
enum GameMode: string
{
    case Flags = 'flags';
    case Location = 'location';
    case Pinpoint = 'pinpoint';
    case Explore = 'explore';

    /**
     * The selectable quiz modes shown as cards on the start screen. Explore is
     * deliberately excluded — it's launched from a single-click link instead.
     *
     * @return self[]
     */
    public static function quizModes(): array
    {
        return [self::Flags, self::Location, self::Pinpoint];
    }

    /** Title shown on the mode card. */
    public function title(): string
    {
        return match ($this) {
            self::Flags => 'Flags',
            self::Location => 'Locations',
            self::Pinpoint => 'Pinpoint',
            self::Explore => 'Explore',
        };
    }

    /** One-line description shown on the mode card. */
    public function description(): string
    {
        return match ($this) {
            self::Flags => 'Name all the flags, one at a time, against the clock.',
            self::Location => 'Find each highlighted country on the world map.',
            self::Pinpoint => 'See a flag and its country — click where it belongs on the map.',
            self::Explore => 'Browse every country and its flag on the map — no clock.',
        };
    }

    /** Whether the mode is played on the world map rather than the flag deck. */
    public function usesMap(): bool
    {
        return $this !== self::Flags;
    }

    /**
     * Whether answers are given by clicking the map. Only Pinpoint asks for a
     * place instead of a word, so it is the one mode where a click on a country
     * is the answer — everywhere else a click only moves the view.
     */
    public function answersByClick(): bool
    {
        return $this === self::Pinpoint;
    }

    /** Whether the start screen shows the settings panel for this mode. */
    public function hasSettings(): bool
    {
        return $this !== self::Explore;
    }

    /**
     * Whether the navigation setting means anything here. In Pinpoint it does
     * not: there is nothing to navigate between (the question is a flag, not a
     * place on the map) and nothing to list (the flag is already on screen).
     */
    public function hasNavToggle(): bool
    {
        return $this === self::Flags || $this === self::Location;
    }

    /** Label for the per-mode navigation toggle on the start screen. */
    public function navToggleLabel(): string
    {
        return $this === self::Location ? 'Free navigation' : 'Show available flags';
    }

    /** Description for the per-mode navigation toggle on the start screen. */
    public function navToggleDescription(): string
    {
        return $this === self::Location
            ? 'Tap any country on the map to jump between them'
            : 'List the flags you have not answered yet';
    }
}
