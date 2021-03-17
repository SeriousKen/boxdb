<?php

namespace Serious\BoxDB\Query\Expression;

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
    protected $match;

    /**
     * 
     */
    protected $expressions = [];

    /**
     * 
     */
    public function __construct($match, ExpressionInterface ...$expressions)
    {
        $this->match = $match;
        $this->expressions = $expressions;
    }

    public function getSQL(string $document): string
    {
        $operator = $this->match == self::ALL ? ' AND ' : ' OR ';
        $negate   = $this->match == self::NONE ? 'NOT ' : '';

        return $negate .'('. join($operator, array_map(function (ExpressionInterface $expression) use ($document) {
            return $expression->getSQL($document);
        }, $this->expressions)) .')';
    }

    public function getParameters(): array
    {
        return array_merge(...array_map(function (ExpressionInterface $expression) {
            return $expression->getParameters();
        }, $this->expressions));
    }
}