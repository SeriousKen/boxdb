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

    public static function getOrderColumn($document, $field, $order)
    {
        return self::getColumn($document, $field).' '.($order == -1 ? 'DESC' : 'ASC');
    }

    public static function getOrderColumns($document, array $fields): string
    {
        $sort = [];

        foreach ($fields as $field => $order) {
            $sort[] = self::getOrderColumn($document, $field, $order);
        }

        return join(', ', $sort);
    }
}