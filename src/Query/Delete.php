<?php

namespace Serious\BoxDB\Query;

use Serious\BoxDB\Query\Expression\ExpressionBuilder;
use Serious\BoxDB\Query\Expression\ExpressionInterface;
use SQLite3Result;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Delete extends Query
{
    protected function getQuery(): string
    {
        return 'DELETE FROM '. $this->table;
    }

    protected function resolveOptions(OptionsResolver $resolver)
    {
        $resolver->define('filter')
            ->allowedTypes('null', 'array', ExpressionInterface::class)
            ->normalize(function (Options $options, $value) {
                if (is_array($value)) {
                    return ExpressionBuilder::create()->fromFilter($value);
                }

                return $value;
            })
            ->default(null);
    }
}