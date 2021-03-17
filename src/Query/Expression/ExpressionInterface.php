<?php

namespace Serious\BoxDB\Query\Expression;

interface ExpressionInterface
{
    public function getSQL(string $document): string;
    public function getParameters(): array;
}