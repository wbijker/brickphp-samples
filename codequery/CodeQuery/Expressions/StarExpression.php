<?php

namespace CodeQuery\Expressions;

class StarExpression implements SqlExpression
{
    function toSql(): string
    {
        return "*";
    }
}