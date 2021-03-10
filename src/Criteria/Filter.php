<?php

namespace Serious\BoxDB\Criteria;

use Faker\Provider\ar_JO\Company;
use RuntimeException;

final class Filter implements CriteriaInterface
{
    protected $criteria;

    public static function matches(array $filter)
    {
        return new Filter($filter);
    }

    public function __construct(array $filter)
    {
        $this->criteria = $this->createCriteriaFromFilter($filter);
    }

    public function getQuery(): string
    {
        return $this->criteria->getQuery();
    }

    public function getParameters(): array
    {
        return $this->criteria->getParameters();
    }

    protected function createCriteriaFromFilter(array $filter): CriteriaInterface
    {
        $criteria = [];

        foreach ($filter as $field => $expression) {
            if (strpos($field, '$') === 0) {
                $criteria[] = $this->createCriteriaForCompositeFilter($expression, $field);
            } elseif (is_array($expression)) {
                $criteria[] = $this->createCriteriaForField($field, $expression);
            } else {
                $criteria[] = new Comparison($field, $expression, Comparison::EQ);
            }
        }

        if (count($criteria) == 1) {
            return $criteria[0];
        } else {
            return new Composite($criteria, Composite::ALL);
        }
    }

    protected function createCriteriaForCompositeFilter($filters, $operator): CriteriaInterface
    {
        $criteria = [];

        foreach ($filters as $filter) {
            $criteria[] = $this->createCriteriaFromFilter($filter);
        }

        if (count($criteria) == 1) {
            return $criteria[0];
        }

        switch ($operator) {
            case '$all':
                return new Composite($criteria, Composite::ALL);

            case '$any':
                return new Composite($criteria, Composite::ANY);

            case '$none':
                return new Composite($criteria, Composite::NONE);

            default:
                throw new RuntimeException(sprintf("Unknown composite operator '%s'.", $operator));
        }
    }

    protected function createCriteriaForField($field, array $expression): CriteriaInterface
    {
        $criteria = [];

        foreach ($expression as $operator => $value) {
            switch ($operator) {
                case '$eq':
                    $criteria[] = new Comparison($field, $value, Comparison::EQ);
                    break;

                case '$ne':
                    $criteria[] =new Comparison($field, $value, Comparison::NE);
                    break;

                case '$lt':
                    $criteria[] = new Comparison($field, $value, Comparison::LT);
                    break;

                case '$lte':
                    $criteria[] = new Comparison($field, $value, Comparison::LTE);
                    break;

                case '$gt':
                    $criteria[] = new Comparison($field, $value, Comparison::GT);
                    break;

                case '$gte':
                    $criteria[] = new Comparison($field, $value, Comparison::GTE);
                    break;

                case '$in':
                    $criteria[] = new Comparison($field, $value, Comparison::IN);
                    break;

                case '$nin':
                case '$notIn':
                    $criteria[] = new Comparison($field, $value, Comparison::NIN);
                    break;

                case '$contains':
                case '$containsAll':
                    $criteria[] = new Contains($field, $value, Contains::ALL);
                    break;

                case '$containsAny':
                    $criteria[] = new Contains($field, $value, Contains::ANY);
                    break;

                case '$containsNone':
                case '$notContains':
                    $criteria[] = new Contains($field, $value, Contains::NONE);
                    break;

                default:
                    throw new RuntimeException(sprintf("Unknown filter operator '%s'.", $operator));
            }
        }

        if (count($criteria) == 1) {
            return $criteria[0];
        } else {
            return new Composite($criteria, Composite::ALL);
        }
    }
}