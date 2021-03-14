<?php

namespace Serious\BoxDB;

use IteratorAggregate;
use Psr\EventDispatcher\EventDispatcherInterface;
use SQLite3Result;

class Cursor implements IteratorAggregate
{
    protected $result;

    protected $dispatcher;

    public function __construct(SQLite3Result $result, ?EventDispatcherInterface $dispatcher = null)
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
            $row['document'] = json_decode($row['document'], true);

            return $row;
        }

        return false;
    }
}