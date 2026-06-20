<?php

namespace Samples\FlagQuiz;

/**
 * Which screen the game is on. Persisted as session state — the
 * {@see \BrickPHP\State\SessionStateManager} stores native PHP values, so the
 * enum instance round-trips across requests untouched.
 */
enum GamePhase: string
{
    case Start = 'start';
    case Playing = 'playing';
    case Finished = 'finished';
}
