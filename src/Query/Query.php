<?php

namespace Serious\BoxDB\Query;

use SQLite3;
use SQLite3Result;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class Query implements QueryInterface
{
    protected $connection;

    protected $table;

    protected $options;

    public function __construct(SQLite3 $connection, string $table, array $options = [])
    {
        $this->connection = $connection;
        $this->table      = $table;

        $resolver = new OptionsResolver();
        $this->resolveOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    public function execute(): SQLite3Result
    {
        $query = $this->getQuery();
        $parameters = [];

        if (isset($this->options['filter'])) {
            $query .= ' WHERE '.$this->options['filter']->getSQL('document');
            $parameters = $this->options['filter']->getParameters();
        }

        if (isset($this->options['sort'])) {
            $query .= ' ORDER BY '. Helper::getOrderBy($this->options['sort']);
        }

        if (isset($this->options['limit'])) {
            $query .= ' LIMIT ? OFFSET ?';
            $parameters[] = $this->options['limit'];
            $parameters[] = $this->options['skip'];
        }

        echo $query."\n";
        var_dump($parameters);
        $statement = $this->connection->prepare($query);

        foreach ($parameters as $parameter => $value) {
            $statement->bindValue($parameter + 1, $value);
        }

        return $statement->execute();
    }

    abstract protected function resolveOptions(OptionsResolver $resolver);

    abstract protected function getQuery(): string;
}