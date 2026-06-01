<?php

namespace Samples\News;

use DateTimeImmutable;

class IndustryNewsItem
{
    public function __construct(
        public string $title,
        public DateTimeImmutable $date,
    ) {}

    public function formattedDate(): string
    {
        return $this->date->format('j M');
    }
}
