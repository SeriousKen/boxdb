<?php

namespace Serious\BoxDB;

use Serious\BoxDB\Criteria\CriteriaInterface;
use Serious\BoxDB\Utils\Query;
use SQLite3;

class Collection
{
    protected $connection;

    protected $name;

    protected $defaults;

    protected $tableName;

    public function __construct(SQLite3 $connection, string $name, array $defaults = [])
    {
        $this->connection = $connection;
        $this->name = $name;
        $this->defaults = $defaults;
        $this->tableName = $this->getTableName();
    }

    public function getName()
    {
        return $this->name;
    }

    public function createIndex(string $name, array $fields)
    {
        $sql = sprintf('CREATE INDEX IF NOT EXISTS %s ON %s (%s)',
            $this->getIndexName($name),
            $this->tableName,
            Query::buildSort($fields)
        );    
        $this->connection->exec($sql);
    }

    public function dropIndex(string $name)
    {
        $sql = sprintf('DROP INDEX IF EXISTS %s', $this->getIndexName($name));
        $this->connection->exec($sql);
    }

    public function save(array $document)
    {
        $document = array_merge($this->defaults, $document);

        $id = $document['_id'] ?? null;
        unset($document['_id']);

        $path = $document['_path'] ?? null;
        unset($document['_path']);

        $sql = sprintf('REPLACE INTO %s (_id, _path, document) VALUES (?, ?, ?)', $this->tableName);
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $id);
        $stmt->bindValue(2, $path);
        $stmt->bindValue(3, json_encode($document));
        $stmt->execute();
    }

    public function count(?CriteriaInterface $where = null) {
        $sql = sprintf('SELECT count(*) FROM %s', $this->tableName);

        if ($where) {
            $sql .= sprintf(' WHERE %s', $where->getQuery());
        }

        $stmt = $this->connection->prepare($sql);

        if ($where) {
            Query::bindParameters($stmt, $where->getParameters());
        }

        $result = $stmt->execute();

        return $result->fetchArray(SQLITE3_NUM)[0];
    }

    public function distinct(string $field, ?CriteriaInterface $where = null): array
    {
        $sql = sprintf("SELECT DISTINCT json_each.value FROM %s, json_each(document, '$.%s')",
            $this->tableName,
            Query::getSQLForField($field)
        );

        $stmt = $this->connection->prepare($sql);

        if ($where) {
            Query::bindParameters($stmt, $where->getParameters());
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
        $sql = sprintf('SELECT _id, _path, document FROM %s WHERE %s',
            $this->tableName,
            $where->getQuery()
        );

        if (isset($options['sort'])) {
            $sql .= sprintf(' ORDER BY %s', Query::buildSort($options['sort']));
        }

        if (isset($options['limit'])) {
            $sql .= sprintf(" LIMIT %d OFFSET %d",
                $options['limit'],
                $options['skip'] ?? 0
            );
        }

        $stmt = $this->connection->prepare($sql);
        Query::bindParameters($stmt, $where->getParameters());
        
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
        $sql = sprintf('DELETE FROM %s WHERE %s',
            $this->tableName,
            $where->getQuery()
        );
        $stmt = $this->connection->prepare($sql);
        Query::bindParameters($stmt, $where->getParameters());
    }

    protected function getTableName()
    {
        return 'box_collection_'.$this->name;
    }

    protected function getIndexName($name)
    {
        return 'box_index_'.$this->name.'_'.$name;
    }
}