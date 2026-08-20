<?php

namespace Samples\FlagQuiz;

/**
 * What the app is doing: playing a quiz, or browsing the map freely.
 *
 * The quiz used to come in named flavours here — Flags, Locations, Pinpoint.
 * It doesn't any more: a quiz is now a set of shown attributes pointed at one
 * asked-for attribute ({@see Quiz}), which covers those three and every other
 * pairing of the four. All that is left of a "mode" is whether there is a
 * question at all.
 *
 * Persisted as session state (see {@see GamePhase}).
 */
enum GameMode: string
{
    case Quiz = 'quiz';
    case Explore = 'explore';
}
