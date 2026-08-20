<?php

namespace Samples\FlagQuiz;

/**
 * How a wrong answer was given — which decides how the results screen reads it
 * back. A typed guess is an attempt at spelling the same word, so it is worth
 * aligning letter by letter against the answer ({@see GuessDiff}). A pinpointed
 * one is a different country altogether: "Chad" against "Niger" shares letters
 * by coincidence, and diffing them would dress that coincidence up as a near
 * miss. Named instead.
 */
enum GuessKind: string
{
    case Typed = 'typed';
    case Picked = 'picked';
}
