<?php

namespace Serious\BoxDB\Query;

use Symfony\Component\OptionsResolver\OptionsResolver;

class Select extends Query
{
    protected function getQuery(): string
    {
        return 'SELECT _name, _path, _pathname, _created_at, _updated_at, document FROM '. $this->table;
    }

    protected function resolveOptions(OptionsResolver $resolver)
    {
        $resolver->define('sort')->allowedTypes('null', 'int[]')->default(null);
        $resolver->define('limit')->allowedTypes('null', 'int')->default(null);
        $resolver->define('skip')->allowedTypes('int')->default(0);
    }
}