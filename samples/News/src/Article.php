<?php

namespace Samples\News;

use DateTimeImmutable;

class Article
{
    public function __construct(
        public string $category,
        public string $title,
        public DateTimeImmutable $date,
        public string $coverImage,
        public string $excerpt,
        public string $content = '',
    ) {}

    public function formattedDate(): string
    {
        return $this->date->format('j M Y');
    }

    /**
     * URL-safe slug derived from the title — used as the `/article/<slug>`
     * path segment. Lowercased, non-alphanumerics collapsed to `-`, trimmed.
     */
    public function slug(): string
    {
        $s = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $this->title));
        return trim($s, '-');
    }
}
