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
            $document = array_merge($row, json_decode($row['document'], true));
            unset($document['document']);

            return $document;
        }

        return false;
    }
}