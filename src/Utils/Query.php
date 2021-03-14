<?php

namespace Serious\BoxDB\Utils;

use SQLite3Stmt;

class Query
{
    public static function getSQLForField(string $field): string
    {
        if (strpos($field, '_') === 0) {
            return $field;
        } else {
            return sprintf("json_extract(document, '$.%s')", $field);
        }
    }

    public static function buildSort(array $fields)
    {
        $sort = [];

        foreach ($fields as $field => $order) {
            $sort[] = Query::getSQLForField($field).' '.($order == -1 ? 'DESC' : 'ASC');
        }

        return join(', ', $sort);
    }

    public static function placeholders(array $parameters): string
    {
        return join(', ', array_fill(0, count($parameters), '?'));
    }

    public static function bindParameters(SQLite3Stmt $stmt, array $parameters)
    {
        foreach ($parameters as $parameter => $value) {
            $stmt->bindValue($parameter + 1, $value);
        }
    }
}