<?php

namespace Serious\BoxDB\Query;

class Delete extends Query
{
    protected function getQuery(): string
    {
        return 'DELETE FROM '. $this->table;
    }
}