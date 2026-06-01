<?php

namespace CodeQuery\Sources;


abstract class SqlSource
{
    public string $alias = "";

    function setAlias(string $alias): void
    {
        $this->alias = $alias;
    }

    abstract function toSql(): string;
}

