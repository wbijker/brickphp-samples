<?php

namespace CodeQuery\Schema\Traits;

trait UniqueTrait
{
    protected bool $unique = false;

    public function unique(): static
    {
        $this->unique = true;
        return $this;
    }
}