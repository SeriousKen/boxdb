<?php

namespace Serious\BoxDB\Criteria;

use RuntimeException;
use Serious\BoxDB\Utils\Query;

final class Comparison implements CriteriaInterface
{
    const EQ  = 1;
    const NE  = 2;
    const LT  = 3;
    const LTE = 4;
    const GT  = 5;
    const GTE = 6;
    const IN  = 7;
    const NIN = 8;

    protected $operators = [
        self::EQ  => 'IS',
        self::NE  => 'IS NOT',
        self::LT  => '<',
        self::LTE => '<=',
        self::GT  => '>=',
        self::GTE => '<=',
        self::IN  => 'IN',
        self::NIN => 'NOT IN',
    ];

    protected $field;

    protected $parameters;

    protected $operator;

    public function __construct($field, $value, $operator = self::EQ)
    {
        $this->field = $field;
        $this->parameters = is_array($value) ? $value : [$value];
        $this->operator = $operator;
    }

    public function getQuery(): string
    {
        $sql = Query::getSQLForField($this->field).' '.$this->getOperator($this->operator);

        if ($this->operator == self::IN || $this->operator == self::NIN) {
            $sql .= ' ('. Query::placeholders($this->parameters). ')';
        } else {
            $sql .= ' ?';
        }

        return $sql;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    protected function getOperator(string $operator): string
    {
        if (!isset($this->operators[$operator])) {
            throw new RuntimeException(sprintf("Invalid comparison operator '%s'.", $operator));
        }

        return $this->operators[$this->operator];
    }
}