<?php

namespace Serious\BoxDB\Query;

final class Helper
{
    public static function getColumn($document, $field)
    {
        return strpos($field, '_') === 0 ? $field : sprintf("json_extract(%s, '$.%s')", $document, $field);
    }

    public static function getPlaceholders(array $parameters)
    {
        return join(', ', array_fill(0, count($parameters), '?'));
    }

    public static function getOrderBy(array $fields): string
    {
        $sort = [];

        foreach ($fields as $field => $order) {
            $sort[] = self::getColumn($field).' '.($order == -1 ? 'DESC' : 'ASC');
        }

        return join(', ', $sort);
    }
}