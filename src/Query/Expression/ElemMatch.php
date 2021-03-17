<?php

namespace Serious\BoxDB\Query\Expression;

use Serious\BoxDB\Query\Helper;

class ElemMatch implements ExpressionInterface
{
    protected $field;

    protected $where;

    public function __construct(string $field, ExpressionInterface $where)
    {
        $this->field = $field;
        $this->where = $where;
    }

    public function getSQL(string $document): string
    {
        return "EXISTS (SELECT json_each.value AS _element FROM json_each(document, '$.". $this->field ."') WHERE ". $this->where->getSQL('_element') .")";
    }

    public function getParameters(): array
    {
        return $this->where->getParameters();
    }
}