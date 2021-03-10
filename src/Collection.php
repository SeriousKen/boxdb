<?php

namespace Serious\BoxDB;

use Ramsey\Uuid\Uuid;
use Serious\BoxDB\Criteria\CriteriaInterface;
use Serious\BoxDB\Utils\Query;
use SQLite3;
use SQLite3Stmt;

class Collection
{
    protected $connection;

    protected $name;

    protected $tableName;

    public function __construct(SQLite3 $connection, string $name)
    {
        $this->connection = $connection;
        $this->name = $name;
        $this->tableName = $this->getTableName();
    }

    public function createIndex(string $name, array $fields)
    {
        $sql = sprintf('CREATE INDEX IF NOT EXISTS %s ON %s (%s)', $this->getIndexName($name), $this->tableName, $this->buildSort($fields));
        $this->connection->exec($sql);
    }

    public function dropIndex(string $name)
    {
        $sql = sprintf('DROP INDEX IF EXISTS %s', $this->getIndexName($name));
        $this->connection->exec($sql);
    }

    public function insert(array $document)
    {
        $_id = Uuid::uuid4()->getBytes();
        $document['_id'] = Uuid::fromBytes($_id)->toString();
        $sql = sprintf('INSERT INTO %s (_id, document) VALUES (?, ?)', $this->tableName);
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $_id);
        $stmt->bindValue(2, json_encode($document));
        $stmt->execute();
    }

    public function update(array $document)
    {
        $_id = Uuid::fromString($document['_id'])->getBytes();
        $sql = sprintf('UPDATE %s SET document = ? WHERE _id = ?', $this->tableName);
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $_id);
        $stmt->bindValue(2, json_encode($document));
        $stmt->execute();
    }

    public function count(?CriteriaInterface $where = null) {
        $sql = sprintf('SELECT count(*) FROM %s', $this->tableName);

        if ($where) {
            $sql .= ' WHERE '.$where->getQuery();
        }

        $stmt = $this->connection->prepare($sql);

        if ($where) {
            $this->bindParameters($stmt, $where->getParameters());
        }

        $result = $stmt->execute();

        return $result->fetchArray(SQLITE3_NUM)[0];
    }

    public function distinct(string $field, ?CriteriaInterface $where = null): array
    {
        $sql = sprintf('SELECT DISTINCT json_each.value FROM %s, json_each(document, %s)',
            $this->tableName,
            Query::quoteField($field)
        );

        $stmt = $this->connection->prepare($sql);

        if ($where) {
            $this->bindParameters($stmt, $where->getParameters());
        }

        $result = $stmt->execute();
        $distinct = [];

        while ($value = $result->fetchArray(SQLITE3_NUM)) {
            $distinct[] = $value[0];
        }

        return $distinct;
    }

    public function find(CriteriaInterface $where, array $options = [])
    {
        $sql = sprintf('SELECT * FROM %s WHERE %s', $this->tableName, $where->getQuery());

        if (isset($options['sort'])) {
            $sql .= ' ORDER BY '.$this->buildSort($options['sort']);
        }

        if (isset($options['limit'])) {
            $sql .= sprintf(' LIMIT %d OFFSET %d', $options['limit'], $options['skip'] ?? 0);
        }

        $stmt = $this->connection->prepare($sql);
        $this->bindParameters($stmt, $where->getParameters());
        
        return new Cursor($stmt->execute());
    }

    public function findOne(CriteriaInterface $where, array $options = [])
    {
        $options['limit'] = 1;
        $result = $this->find($where, $options);

        return $result->fetch();
    }

    public function delete(CriteriaInterface $where)
    {
        $sql = sprintf('DELETE FROM %s WHERE %s', $this->tableName, $where->getQuery());
        $stmt = $this->connection->prepare($sql);
        $this->bindParameters($stmt, $where->getParameters());
    }

    protected function getTableName()
    {
        return 'box_collection_'.$this->name;
    }

    protected function getIndexName($name)
    {
        return 'box_index_'.$this->name.'_'.$name;
    }

    protected function buildSort(array $fields)
    {
        $sort = [];

        foreach ($fields as $field => $order) {
            $sort[] = sprintf('json_extract(document, %s) %s',
                Query::quoteField($field),
                $order == -1 ? 'DESC' : 'ASC'
            );
        }

        return join(', ', $sort);
    }

    protected function bindParameters(SQLite3Stmt $stmt, array $parameters)
    {
        foreach ($parameters as $parameter => $value) {
            $stmt->bindValue($parameter + 1, $value);
        }
    }
}