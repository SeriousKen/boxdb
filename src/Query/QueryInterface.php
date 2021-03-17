<?php

namespace Serious\BoxDB\Query;

use Serious\BoxDB\Result;
use SQLite3Result;

interface QueryInterface
{
    public function execute(): SQLite3Result;
}