<?php

namespace CodeQuery\Schema\Traits;

trait NullableTrait
{
    protected bool $nullable = false;

    public function nullable(): static
    {
        $this->nullable = true;
        return $this;
    }
}

