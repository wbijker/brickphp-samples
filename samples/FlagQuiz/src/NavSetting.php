<?php

namespace Samples\FlagQuiz;

/**
 * The wording of the one setting that changes meaning with the question being
 * asked — moving between questions by map, or by flag deck. Which one (if
 * either) applies is decided by {@see Quiz::navSetting()}; this carries what
 * the start screen should call it.
 */
final class NavSetting
{
    public function __construct(
        public readonly string $label,
        public readonly string $description,
    ) {}
}
