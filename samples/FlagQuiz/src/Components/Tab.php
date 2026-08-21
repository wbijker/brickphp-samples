<?php

namespace Samples\FlagQuiz\Components;

/**
 * One tab of a {@see TabView}: what it is called, and the key that says which
 * one is open. The key rather than a position, so the caller can decide which
 * is open from what it already knows instead of counting.
 */
final class Tab
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
    ) {}
}
