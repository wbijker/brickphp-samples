<?php

namespace CodeQuery\Schema\Traits;

trait PrimaryKeyTrait
{
    protected bool $primaryKey = false;

    public function primaryKey(): static
    {
        $this->primaryKey = true;
        return $this;
    }
}

