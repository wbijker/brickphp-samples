<?php

namespace Samples\Docs\Data;

/**
 * Plain data container for one element's documentation entry.
 *
 * Each example carries the source `code` (shown verbatim in a code block)
 * and an optional `render` closure that returns the same UI element so the
 * page can show a live preview. Keep code + render in sync — the closure
 * is the source of truth for what the preview looks like; the string is
 * what the reader sees.
 *
 * @phpstan-type ExampleArray array{caption?:string, code:string, render?:\Closure}
 */
class ElementDoc
{
    /**
     * @param array<int, array{caption?:string, code:string, render?:\Closure}> $examples
     * @param array<int, string> $relatedSlugs
     */
    public function __construct(
        public string $slug,
        public string $name,
        public string $factory,
        public string $category,
        public string $summary,
        public string $description,
        public array $examples = [],
        public array $relatedSlugs = [],
    ) {}
}
