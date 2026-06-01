<?php

namespace CodeQuery\Schema;

use CodeQuery\Sources\TableSource;

class TableBuilder
{
    public TableSource $source;

    public function __construct(public Table $table)
    {
        $this->source = new TableSource(get_class($table), $this->table);
    }

    function tableName(string $name): void
    {
        $this->source->tableName = $name;
    }

    function int(string $column): IntColumnDefinition
    {
        return new IntColumnDefinition($column, $this->source);
    }

    function string(string $column): StringColumnDefinition
    {
        return new StringColumnDefinition($column, $this->source);
    }

    function bool(string $column): BoolColumnDefinition
    {
        return new BoolColumnDefinition($column, $this->source);
    }

    function float(string $column): FloatColumnDefinition
    {
        return new FloatColumnDefinition($column, $this->source);
    }
}