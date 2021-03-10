<?php

namespace Serious\BoxDB;

use IteratorAggregate;
use SQLite3Result;

class Cursor implements IteratorAggregate
{
    protected $result;

    public function __construct(SQLite3Result $result)
    {
        $this->result = $result;
    }

    public function getIterator()
    {
        $this->result->reset();
        
        while ($document = $this->fetch()) {
            yield $document['_id'] => $document;
        }
    }

    public function fetch()
    {
        if ($row = $this->result->fetchArray(SQLITE3_ASSOC)) {
            return json_decode($row['document'], true);
        }

        return false;
    }
}