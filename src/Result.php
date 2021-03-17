<?php

namespace Serious\BoxDB;

use IteratorAggregate;
use SQLite3Result;

class Result implements IteratorAggregate
{
    protected $result;

    public function __construct(SQLite3Result $result)
    {
        $this->result = $result;
    }

    public function getIterator()
    {
        while ($document = $this->fetch()) {
            yield $document;
        }
    }

    public function fetch()
    {
        if ($row = $this->result->fetchArray(SQLITE3_ASSOC)) {
            $document = json_decode($row['document'], true);
            unset($row['document']);

            return array_merge($row, $document);
        }

        return false;
    }

    public function fetchAll(): array
    {
        return iterator_to_array($this);
    }
}