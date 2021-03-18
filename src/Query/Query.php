<?php

namespace Serious\BoxDB\Query;

use InvalidArgumentException;
use Serious\BoxDB\Query\Expression\ExpressionBuilder;
use Serious\BoxDB\Query\Expression\ExpressionInterface;
use SQLite3;
use SQLite3Result;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class Query implements QueryInterface
{
    protected $connection;

    protected $table;

    protected $filter;

    protected $options;

    public function __construct(SQLite3 $connection, string $table, $filter = null, array $options = [])
    {
        $this->connection = $connection;
        $this->table      = $table;

        if ($filter) {
            if (is_array($filter)) {
                $this->filter = ExpressionBuilder::create()->fromFilter($filter);
            } elseif ($filter instanceof ExpressionInterface) {
                $this->filter = $filter;
            } else {
                throw new InvalidArgumentException(sprintf('Filter must be an array or an instance of %s', ExpressionInterface::class));
            }
        }

        $resolver = new OptionsResolver();
        $this->resolveOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    public function execute(): SQLite3Result
    {
        $query = $this->getQuery();
        $parameters = [];

        if (isset($this->filter)) {
            $query .= ' WHERE '.$this->filter->toSQL('document');
            $parameters = $this->filter->getParameters();
        }

        if (isset($this->options['sort'])) {
            $query .= ' ORDER BY '. Helper::getOrderColumns('document', $this->options['sort']);
        }

        if (isset($this->options['limit'])) {
            $query .= ' LIMIT ? OFFSET ?';
            $parameters[] = $this->options['limit'];
            $parameters[] = $this->options['skip'];
        }

        $statement = $this->connection->prepare($query);

        foreach ($parameters as $parameter => $value) {
            $statement->bindValue($parameter + 1, $value);
        }

        return $statement->execute();
    }

    protected function resolveOptions(OptionsResolver $resolver)
    {

    }

    abstract protected function getQuery(): string;
}