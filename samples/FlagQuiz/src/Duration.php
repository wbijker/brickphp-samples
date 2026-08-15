<?php

namespace Samples\FlagQuiz;

/**
 * A span of whole seconds, and how it reads on a clock face. The quiz shows
 * elapsed time in two places — the running scorebar and the results screen —
 * and both want the same `m:ss`, so the count and its formatting travel
 * together rather than as a bare int at one end and a bare string at the other.
 */
final class Duration
{
    public function __construct(public readonly int $seconds) {}

    /** Seconds since a starting timestamp, floored at zero. */
    public static function since(int $startTime): self
    {
        return new self(max(0, time() - $startTime));
    }

    /** `m:ss` — minutes uncapped, so a long game reads 12:07 rather than wrapping. */
    public function clock(): string
    {
        return intdiv($this->seconds, 60) . ':' . str_pad((string)($this->seconds % 60), 2, '0', STR_PAD_LEFT);
    }
}
