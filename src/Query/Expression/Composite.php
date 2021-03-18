<?php

namespace Serious\BoxDB\Query\Expression;

use InvalidArgumentException;

/**
 * Composite expression combines multiple expressions together.
 */
final class Composite implements ExpressionInterface
{
    const ANY  = 'ANY';
    const ALL  = 'ALL';
    const NONE = 'NONE';

    /**
     * 
     */
    protected $matches;

    /**
     * 
     */
    protected $expressions = [];

    /**
     * 
     */
    public function __construct($matches, ExpressionInterface ...$expressions)
    {
        if (!in_array($matches, [self::ANY, self::ANY, self::NONE])) {
            throw new InvalidArgumentException(sprintf("Invalid composite match operator '%s'", $matches));
        }

        $this->matches = $matches;
        $this->expressions = $expressions;
    }

    public function toSQL(string $document): string
    {
        $operator = $this->matches == self::ALL ? ' AND ' : ' OR ';
        $negate   = $this->matches == self::NONE ? 'NOT ' : '';

        return $negate .'('. join($operator, array_map(function (ExpressionInterface $expression) use ($document) {
            return $expression->toSQL($document);
        }, $this->expressions)) .')';
    }

    public function getParameters(): array
    {
        return array_merge(...array_map(function (ExpressionInterface $expression) {
            return $expression->getParameters();
        }, $this->expressions));
    }
}