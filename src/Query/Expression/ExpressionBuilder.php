<?php

namespace Serious\BoxDB\Query\Expression;

use RuntimeException;

class ExpressionBuilder
{
    protected $comparison = [
        '$eq'            => 'eq',
        '$ne'            => 'ne',
        '$lt'            => 'lt',
        '$lte'           => 'lte',
        '$gt'            => 'gt',
        '$gte'           => 'gte',
        '$in'            => 'in',
        '$nin'           => 'notIn',
        '$like'          => 'like',
        '$notLike'       => 'notLike',
        '$matches'       => 'matches',
        '$glob'          => 'glob',
        '$elemMatch'     => 'elemMatchAny',
        '$elemMatchAny'  => 'elemMatchAny',
        '$elemMatchAll'  => 'elemMatchAll',
        '$elemMatchNone' => 'elemMatchNone',
    ];

    protected $composite = [
        '$any'  => 'any',
        '$all'  => 'all',
        '$none' => 'none', 
    ];

    public static function create(): self
    {
        return new self();
    }

    public function eq(string $field, $value): ExpressionInterface
    {
        return new Comparison($field, Comparison::EQ, $value);
    }

    public function ne(string $field, $value): ExpressionInterface
    {
        return new Comparison($field, Comparison::NE, $value);
    }

    public function lt(string $field, $value): ExpressionInterface
    {
        return new Comparison($field, Comparison::LT, $value);
    }

    public function lte(string $field, $value): ExpressionInterface
    {
        return new Comparison($field, Comparison::LTE, $value);
    }

    public function gt(string $field, $value): ExpressionInterface
    {
        return new Comparison($field, Comparison::GT, $value);
    }

    public function gte(string $field, $value): ExpressionInterface
    {
        return new Comparison($field, Comparison::GTE, $value);
    }

    public function in(string $field, array $value): ExpressionInterface
    {
        return new Comparison($field, Comparison::IN, $value);
    }

    public function notIn(string $field, array $value): ExpressionInterface
    {
        return new Comparison($field, Comparison::NOTIN, $value);
    }

    public function like(string $field, string $value): ExpressionInterface
    {
        return new Comparison($field, Comparison::LIKE, $value);
    }

    public function notLike(string $field, string $value): ExpressionInterface
    {
        return new Comparison($field, Comparison::NOTLIKE, $value);
    }

    public function matches(string $field, string $value): ExpressionInterface
    {
        return new Comparison($field, Comparison::MATCHES, $value);
    }

    public function glob(string $field, string $value): ExpressionInterface
    {
        return new Comparison($field, Comparison::GLOB, $value);
    }

    public function elemMatch(string $field, $where, $matches = ElemMatch::ANY): ExpressionInterface
    {
        if (is_array($where)) {
            if (array_intersect_key($where, $this->comparison)) {
                $where = $this->handleField('_document.value', $where);
            } elseif ($where === array_values($where)) {
                $where = $this->in('_document.value', $where);
            } else {
                $where = $this->fromFilter($where);
            }
        } elseif (!$where instanceof ExpressionInterface) {
            $where = $this->eq('_document.value', $where);
        }

        return new ElemMatch($field, $matches, $where);
    }

    public function elemMatchAny(string $field, $where): ExpressionInterface
    {
        return $this->elemMatch($field, $where, ElemMatch::ANY);
    }

    public function elemMatchAll(string $field, $where): ExpressionInterface
    {
        return $this->elemMatch($field, $where, ElemMatch::ALL);
    }

    public function elemMatchNone(string $field, $where): ExpressionInterface
    {
        return $this->elemMatch($field, $where, ElemMatch::NONE);
    }

    public function any(ExpressionInterface ...$expressions): Composite
    {
        return new Composite(Composite::ANY, ...$expressions);
    }

    public function all(ExpressionInterface ...$expressions): Composite
    {
        return new Composite(Composite::ALL, ...$expressions);
    }

    public function none(ExpressionInterface ...$expressions): Composite
    {
        return new Composite(Composite::NONE, ...$expressions);
    }

    public function fromFilter(array $filter): ExpressionInterface
    {
        $expressions = [];

        foreach ($filter as $fieldOrComposite => $value) {
            if (array_key_exists($fieldOrComposite, $this->composite)) {
                $expressions[] = $this->handleComposite($fieldOrComposite, $value);
            } else {
                $expressions[] = $this->handleField($fieldOrComposite, $value);
            }
        }

        return (count($expressions) == 1) ? $expressions[0] : $this->all(...$expressions);
    }

    protected function handleComposite($match, array $filters): ExpressionInterface
    {
        $expressions = [];

        foreach ($filters as $filter) {
            $expressions[] = $this->fromFilter($filter);
        }

        return call_user_func([$this, $this->composite[$match]], ...$expressions);
    }

    protected function handleField($field, $comparison): ExpressionInterface
    {
        if (!is_array($comparison)) {
            return $this->eq($field, $comparison);
        } elseif ($comparison == array_values($comparison)) {
            return $this->in($field, $comparison);
        }

        $expressions = [];

        foreach ($comparison as $operator => $value) {
            if (array_key_exists($operator, $this->comparison)) {
                $expressions[] = call_user_func([$this, $this->comparison[$operator]], $field, $value);
            } else {
                throw new RuntimeException(sprintf("Invalid comparison operator '%s'", $operator));
            }
        }

        return (count($expressions) == 1) ? $expressions[0] : $this->all(...$expressions);
    }
}