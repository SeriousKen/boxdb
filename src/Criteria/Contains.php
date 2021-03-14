<?php

namespace Serious\BoxDB\Criteria;

use Serious\BoxDB\Utils\Query;

final class Contains implements CriteriaInterface
{
    const ANY  = 1;
    const ALL  = 2;
    const ONE  = 3;
    const NONE = 4;

    protected $field;

    protected $parameters = [];

    protected $mode;

    public static function any(string $field, $value)
    {
        return new self($field, $value, self::ANY);
    }

    public static function all(string $field, $value)
    {
        return new self($field, $value, self::ALL);
    }

    public static function one(string $field, $value)
    {
        return new self($field, $value, self::ONE);
    }

    public static function none(string $field, $value)
    {
        return new self($field, $value, self::NONE);
    }

    public function __construct(string $field, $value, $mode = self::ANY)
    {
        $this->field = $field;
        $this->parameters = is_array($value) ? $value : [$value];
        $this->mode = $mode;
    }

    public function getQuery(): string
    {
        switch ($this->mode) {
            case self::ALL:
                $comparison = '= '.count($this->parameters);
                break;

            case self::ONE:
                $comparison = '= 1';
                break;

            case self::NONE:
                $comparison = '= 0';
                break;

            case self::ANY:
            default:
                $comparison = '> 0';
                break;
        }

        return sprintf("(SELECT count(*) FROM json_each(document, '$.%s') WHERE json_each.value IN (%s)) %s",
            $this->field,
            Query::placeholders($this->parameters),
            $comparison
        );
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}