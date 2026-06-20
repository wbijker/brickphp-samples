<?php

namespace Samples\FlagQuiz;

/**
 * The three ways to play. Each case carries its own start-screen copy and the
 * mode-specific wording of the navigation setting, so the screens stay free of
 * magic strings. Persisted as session state (see {@see GamePhase}).
 */
enum GameMode: string
{
    case Flags = 'flags';
    case Location = 'location';
    case Explore = 'explore';

    /** Title shown on the mode card. */
    public function title(): string
    {
        return match ($this) {
            self::Flags => 'Flags',
            self::Location => 'Locations',
            self::Explore => 'Explore',
        };
    }

    /** One-line description shown on the mode card. */
    public function description(): string
    {
        return match ($this) {
            self::Flags => 'Name all the flags, one at a time, against the clock.',
            self::Location => 'Find each highlighted country on the world map.',
            self::Explore => 'Browse every country and its flag on the map — no clock.',
        };
    }

    /** Whether the start screen shows the settings panel for this mode. */
    public function hasSettings(): bool
    {
        return $this !== self::Explore;
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
