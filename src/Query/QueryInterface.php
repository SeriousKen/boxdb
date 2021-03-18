<?php

namespace Serious\BoxDB\Query;

use SQLite3Result;

interface QueryInterface
{
    public function execute(): SQLite3Result;
}