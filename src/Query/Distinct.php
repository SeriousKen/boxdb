<?php

namespace Serious\BoxDB\Query;

use Serious\BoxDB\Query\Expression\ExpressionBuilder;
use Serious\BoxDB\Query\Expression\ExpressionInterface;
use SQLite3;
use SQLite3Result;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Distinct extends Query
{
    protected function getQuery(): string
    {
        return 'SELECT DISTINCT json_each.value AS _value FROM '. $this->table .", json_each(document, '$.". $this->options['field'] ."')";
    }

    protected function resolveOptions(OptionsResolver $resolver)
    {
        $resolver->define('field')->allowedTypes('field', 'string')->required();
        $resolver->define('sort')->default(['_value' => 1]);
    }
}