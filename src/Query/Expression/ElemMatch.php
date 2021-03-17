<?php

namespace Serious\BoxDB\Query\Expression;

use InvalidArgumentException;

class ElemMatch implements ExpressionInterface
{
    const ANY  = 'ANY';
    const ALL  = 'ALL';
    const NONE = 'NONE';

    protected $field;

    protected $matches;

    protected $where;

    public function __construct(string $field, $matches, ExpressionInterface $where)
    {
        if (!in_array($matches, [self::ANY, self::ALL, self::NONE])) {
            throw new InvalidArgumentException(sprintf("Invalid element match operator '%s'", $matches));
        }

        $this->field   = $field;
        $this->matches = $matches;
        $this->where   = $where;
    }

    public function getSQL(string $document): string
    {
        $comparison = [
            self::ANY  => '> 0',
            self::ALL  => "= json_array_length(". $document .", '$.". $this->field ."')",
            self::NONE => '= 0',
        ];
        
        return "(SELECT COUNT(_document.value) FROM json_each(document, '$.". $this->field
            ."') AS _document WHERE ". $this->where->getSQL('_document.value') .") ". $comparison[$this->matches];
    }

    public function getParameters(): array
    {
        return $this->where->getParameters();
    }
}