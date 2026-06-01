<?php

namespace CodeQuery\Schema\Traits;

trait ForeignKeyTrait
{
    protected string $foreignKey = "";


    // ->foreignKey(fn(Category $c) => $c->id);
    public function foreignKey(callable $fk): static
    {
        return $this;
    }
}