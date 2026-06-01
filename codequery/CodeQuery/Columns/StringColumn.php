<?php

namespace CodeQuery\Columns;

use CodeQuery\Expressions\BinaryExpression;
use CodeQuery\Expressions\ConstExpression;
use CodeQuery\Expressions\FunctionExpression;
use CodeQuery\Expressions\SqlExpression;
use CodeQuery\Expressions\StringCaseExpression;

class StringColumn extends Column
{

    public function createAlias(SqlExpression $exp): static
    {
        return new StringColumn($exp);
    }

    private function toExp(string|StringColumn $value): SqlExpression
    {
        return is_string($value)
            ? new ConstExpression($value)
            : $value->exp;
    }

    function equals(string|StringColumn $value): StringColumn
    {
        return new StringColumn(new BinaryExpression($this->exp, BinaryExpression::EQUAL, $this->toExp($value)));
    }

    function notEquals(string|StringColumn $value): StringColumn
    {
        return new StringColumn(new BinaryExpression($this->exp, BinaryExpression::NOT_EQUAL, $this->toExp($value)));
    }

    public string $value;
    function convertFrom(mixed $val): void
    {
        $this->value = (string)$val;
    }

    function length(): StringColumn
    {
        return new StringColumn(new FunctionExpression('LENGTH', [$this->exp]));
    }

    function lower(): StringColumn
    {
        return new StringColumn(new FunctionExpression('LOWER', [$this->exp]));
    }

    function upper(): StringColumn
    {
        return new StringColumn(new FunctionExpression('UPPER', [$this->exp]));
    }

    function trim(): StringColumn
    {
        return new StringColumn(new FunctionExpression('TRIM', [$this->exp]));
    }

    function ltrim(): StringColumn
    {
        return new StringColumn(new FunctionExpression('LTRIM', [$this->exp]));
    }

    function rtrim(): StringColumn
    {
        return new StringColumn(new FunctionExpression('RTRIM', [$this->exp]));
    }

    function substr(int $start, ?int $length = null): StringColumn
    {
        $params = [$this->exp, new ConstExpression($start)];
        if ($length !== null) {
            $params[] = new ConstExpression($length);
        }
        return new StringColumn(new FunctionExpression('SUBSTRING', $params));
    }

    function replace(string $search, string $replace): StringColumn
    {
        return new StringColumn(new FunctionExpression('REPLACE', [
            $this->exp,
            new ConstExpression($search),
            new ConstExpression($replace)
        ]));
    }

    function concat(string|StringColumn ...$values): StringColumn
    {
        $params = [$this->exp];
        foreach ($values as $v) {
            $params[] = $this->toExp($v);
        }
        return new StringColumn(new FunctionExpression('CONCAT', $params));
    }

    function position(string $substring): StringColumn
    {
        return new StringColumn(new FunctionExpression('LOCATE', [
            new ConstExpression($substring),
            $this->exp
        ]));
    }
}

