<?php

namespace CodeQuery\Schema;

use CodeQuery\Columns\StringColumn;
use CodeQuery\Expressions\ColumnExpression;
use CodeQuery\Expressions\SqlExpression;
use CodeQuery\Schema\Traits\ForeignKeyTrait;
use CodeQuery\Schema\Traits\NullableTrait;
use CodeQuery\Schema\Traits\PrimaryKeyTrait;
use CodeQuery\Schema\Traits\UniqueTrait;
use CodeQuery\Sources\TableSource;

class StringColumnDefinition extends StringColumn implements ColumnDefinition
{
    use ForeignKeyTrait;
    use NullableTrait;
    use PrimaryKeyTrait;
    use UniqueTrait;

    public function __construct(
        private string      $column,
        private TableSource $source
    )
    {
        parent::__construct(new ColumnExpression($column, $source));
    }
    
    public function createAlias(SqlExpression $exp): static
    {
        $def = new StringColumnDefinition($this->column, $this->source);
        $def->exp = $exp;
        return $def;
    }

    function buildSchema(): string
    {
        return "";
    }
}