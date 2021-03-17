<?php

namespace Serious\BoxDB\Query\Expression;

use InvalidArgumentException;
use Serious\BoxDB\Query\Helper;

/**
 * Comparison expression.
 */
final class Comparison implements ExpressionInterface
{
    const EQ      = '=';
    const NE      = '!=';
    const NEQ     = '!=';
    const LT      = '<';
    const LTE     = '<=';
    const GT      = '>';
    const GTE     = '>=';
    const IN      = 'IN';
    const NOTIN   = 'NOT IN';
    const LIKE    = 'LIKE';
    const NOTLIKE = 'NOT LIKE';
    const REGEX   = 'MATCHES';
    const MATCHES = 'MATCHES';
    const GLOB    = 'GLOB';

    protected $operators = [
        self::EQ      => 'IS',
        self::NE      => 'IS NOT',
        self::NEQ     => 'IS NOT',
        self::LT      => '<',
        self::LTE     => '<=',
        self::GT      => '>=',
        self::GTE     => '<=',
        self::IN      => 'IN',
        self::NOTIN   => 'NOT IN',
        self::LIKE    => 'LIKE',
        self::NOTLIKE => 'NOT LIKE',
        self::REGEX   => 'REGEXP',
        self::MATCHES => 'REGEXP',
        self::GLOB    => 'GLOB',
    ];

    protected $field;
    
    protected $operator;

    protected $parameters;

    public function __construct($field, $operator, $value)
    {
        if (!array_key_exists($operator, $this->operators)) {
            throw new InvalidArgumentException(sprintf("Invalid comparison operator '%s'", $operator));
        }

        $this->field = $field;
        $this->parameters = is_array($value) ? $value : [$value];
        $this->operator = $operator;
    }

    public function getSQL(string $document): string
    {
        $sql = Helper::getColumn($document, $this->field).' '.$this->operators[$this->operator];

        if ($this->operator == self::IN || $this->operator == self::NOTIN) {
            $sql .= ' ('. Helper::getPlaceholders($this->parameters). ')';
        } else {
            $sql .= ' ?';
        }

        return $sql;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}