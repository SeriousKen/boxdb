<?php

namespace Serious\BoxDB\Utils;

use SQLite3Stmt;

final class SQL
{
    public static function field(string $field): string
    {
        if (strpos($field, '_') === 0) {
            return $field;
        } else {
            return sprintf("json_extract(document, '$.%s')", $field);
        }
    }

    public static function sort(array $fields): string
    {
        $sort = [];

        foreach ($fields as $field => $order) {
            if (is_integer($field)) {
                $field = $order;
                $order = 1;
            }
            
            $sort[] = self::field($field).' '.($order == -1 ? 'DESC' : 'ASC');
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