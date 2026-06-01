<?php

namespace CodeQuery\Expressions;

class StringCaseExpression implements SqlExpression
{
    /**
     * @param SqlExpression[] $whenThen
     * @param SqlExpression|null $else
     */
    public function __construct(private array $whenThen, private ?SqlExpression $else)
    {
        $this->whenThen = $whenThen;
        $this->else = $else;
    }


    public function when(SqlExpression $when, SqlExpression $then): void
    {
        $this->whenThen[] = [$when, $then];
    }

    function else(SqlExpression $else): void
    {
        $this->else = $else;
    }

    function toSql(): string
    {
        return "CASE "
            . implode('', array_map(fn(array $arr) => ' WHEN ' . $arr[0]->toSql() . ' THEN ' . $arr[1]->toSql(), $this->whenThen))
            . ($this->else ? 'ELSE ' . $this->else->toSql() : '')
            . " END";
    }
}