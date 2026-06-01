<?php

namespace CodeQuery\Schema;

interface ColumnDefinition
{
    function buildSchema(): string;
}