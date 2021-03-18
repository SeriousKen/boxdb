<?php

namespace Serious\BoxDB\Query;

class Count extends Query
{
    protected function getQuery(): string
    {
        return 'SELECT count(*) AS _total FROM '. $this->table;
    }
}