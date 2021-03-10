<?php

namespace Serious\BoxDB\Criteria;

interface CriteriaInterface
{
    function getQuery(): string;
    function getParameters(): array;
}