<?php

namespace Serious\BoxDB\Query\Expression;

interface ExpressionInterface
{
    /**
     * Returns the SQL representation of the expression.
     * 
     * @param string $document
     * @return string
     */
    public function toSQL(string $document): string;

    /**
     * Returns the parameters for the query.
     * 
     * @return array
     */
    public function getParameters(): array;
}