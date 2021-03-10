<?php

namespace Serious\BoxDB\Criteria;

final class Composite implements CriteriaInterface
{
    const ANY  = 1;
    const ALL  = 2;
    const NONE = 3;

    protected $criteria = [];

    protected $mode;

    public static function any(array $criteria)
    {
        return new self($criteria, self::ANY);
    }

    public static function all(array $criteria)
    {
        return new self($criteria, self::ALL);
    }

    public static function none(array $criteria)
    {
        return new self($criteria, self::NONE);
    }

    public function __construct(array $criteria, $mode = self::ANY)
    {
        $this->criteria = $criteria;
        $this->mode = $mode;
    }

    public function getQuery(): string
    {
        $operator = $this->mode == self::ALL ? ' AND ' : ' OR ';
        $negate   = $this->mode == self::NONE ? 'NOT ' : '';

        return $negate .'('. join($operator, array_map(function (CriteriaInterface $criteria) {
            return $criteria->getQuery();
        }, $this->criteria)) .')';
    }

    public function getParameters(): array
    {
        return array_merge(...array_map(function (CriteriaInterface $criteria) {
            return $criteria->getParameters();
        }, $this->criteria));
    }
}