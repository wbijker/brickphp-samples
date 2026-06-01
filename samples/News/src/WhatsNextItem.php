<?php

namespace Samples\News;

class WhatsNextItem
{
    public function __construct(
        public string $category,
        public string $title,
        public string $coverImage,
    ) {}
}
