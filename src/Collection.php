<?php

namespace Serious\BoxDB;

use DateTime;
use DateTimeZone;
use Serious\BoxDB\Criteria\CriteriaInterface;
use Serious\BoxDB\Criteria\Filter;
use Serious\BoxDB\Utils\Query;
use SQLite3;
use Symfony\Component\VarDumper\VarDumper;

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

    public function save(array $document): array
    {
        $document = array_merge($this->defaults, $document);
        $date = time();

        $id = $document['_id'] ?? null;
        unset($document['_id']);

        $path = $document['_path'] ?? '/';
        unset($document['_path']);

        unset($document['_created_at']);
        unset($document['_updated_at']);

        /**
         * Try to update the document first.
         */
        $sql = sprintf('UPDATE OR IGNORE %s SET _updated_at = ?, document = ? WHERE _id = ? AND _path = ?', $this->tableName);
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $date);
        $stmt->bindValue(2, json_encode($document));
        $stmt->bindValue(3, $id);
        $stmt->bindValue(4, $path);
        $stmt->execute();

        /**
         * If nothing changed this must be an new document.
         */
        if ($this->connection->changes() == 0 ) {
            $sql = sprintf('INSERT INTO %s (_id, _path, _created_at, _updated_at, document) VALUES (?, ?, ?, ?, ?)', $this->tableName);
            $stmt = $this->connection->prepare($sql);
            $stmt->bindValue(1, $id);
            $stmt->bindValue(2, $path);
            $stmt->bindValue(3, $date);
            $stmt->bindValue(4, $date);
            $stmt->bindValue(5, json_encode($document));
            $stmt->execute();
        }

        $sql = sprintf('SELECT * FROM %s WHERE _id = ? AND _path = ?', $this->tableName);
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $id);
        $stmt->bindValue(2, $path);

        return (new Cursor($stmt->execute()))->fetch();
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
        $sql = sprintf('SELECT * FROM %s WHERE %s',
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