<?php

namespace CodeQuery;

abstract class DbTable
{
    function tableName(): string
    {
        return strtolower(get_class($this));
    }
}