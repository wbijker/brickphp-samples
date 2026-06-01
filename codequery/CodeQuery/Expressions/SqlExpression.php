<?php

namespace CodeQuery\Expressions;

interface SqlExpression
{
    function toSql(): string;
}

