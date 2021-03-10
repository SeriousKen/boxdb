<?php

namespace Serious\BoxDB\Utils;

class Query
{
    public static function quoteField(string $name): string
    {
        return sprintf("'$.%s'", $name);
    }

    public static function placeholders(array $parameters): string
    {
        return join(', ', array_fill(0, count($parameters), '?'));
    }
}