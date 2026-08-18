<?php

namespace Samples\FlagQuiz;

/**
 * One run of consecutive letters sharing a {@see DiffKind}, as produced by
 * {@see GuessDiff}. Runs rather than single characters so the screen renders
 * a handful of nodes instead of one per letter.
 */
final class DiffPart
{
    public function __construct(
        public readonly DiffKind $kind,
        public readonly string $text,
    ) {}
}
