<?php

namespace Serious\BoxDB;

use RuntimeException;
use Serious\BoxDB\Query\Expression\ExpressionInterface;
use Serious\BoxDB\Query\Count;
use Serious\BoxDB\Query\Delete;
use Serious\BoxDB\Query\Distinct;
use Serious\BoxDB\Query\Select;
use Serious\BoxDB\Utils\SQL;
use SQLite3;

class Collection
{
    protected $connection;

    protected $name;

    protected $defaults;

    protected $tableName;

    public function __construct(SQLite3 $connection, string $name, array $options = [])
    {
        $this->connection = $connection;
        $this->name = $name;
        $this->options = $options;
        $this->tableName = $this->getTableName();
    }

    /**
     * 
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Creates an index on the container.
     */
    public function createIndex(string $name, array $fields)
    {
        $sql = sprintf('CREATE INDEX IF NOT EXISTS %s ON %s (%s)',
            $this->getIndexName($name),
            $this->tableName,
            SQL::sort($fields)
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
        $this->connection->exec('SAVEPOINT save_document');

        $date = time();

        if ($name = $document['_name'] ?? null) {
            $path = $document['_path'] ?? null;
            $pathname = rtrim($path, '/').'/'.$name;
        } elseif ($pathname = $document['_pathname'] ?? null) {
            $path = dirname($pathname) == '/' ? null : dirname($pathname);
            $name = basename($pathname);
        } else {
            throw new RuntimeException('Document must have _name or _pathname set');
        }

        unset($document['_name'], $document['_path'], $document['_pathname'], $document['_created_at'], $document['_updated_at']);
        $json = json_encode($document);

        /**
         * Try to insert the document first.
         */
        $sql = sprintf('INSERT OR IGNORE INTO %s (_name, _path, _pathname, _created_at, _updated_at, document) VALUES (?, ?, ?, ?, ?, ?)', $this->tableName);
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $name);
        $stmt->bindValue(2, $path);
        $stmt->bindValue(3, $pathname);
        $stmt->bindValue(4, $date);
        $stmt->bindValue(5, $date);
        $stmt->bindValue(6, $json);
        $stmt->execute();

        /**
         * If nothing was inserted then perform an update.
         */
        if ($this->connection->changes() == 0 ) {
            $sql = sprintf('UPDATE %s SET _updated_at = ?, document = ? WHERE _pathname = ?', $this->tableName);
            $stmt = $this->connection->prepare($sql);
            $stmt->bindValue(1, $date);
            $stmt->bindValue(2, $json);
            $stmt->bindValue(3, $pathname);
            $stmt->execute();
        }

        $this->connection->exec('RELEASE SAVEPOINT save_document');
    }

    public function count($where = null)
    {
        $query = new Count($this->connection, $this->getTableName(), $where);
        $result = $query->execute();

        return $result->fetchArray(SQLITE3_NUM)[0];
    }

    /**
     * Retrieve distinct values for a field.
     * 
     * @param string $field
     * @param ExpressionInterface|null $where
     * @return array
     */
    public function distinct(string $field, $where = null): array
    {
        $distinct = [];
        $query = new Distinct($this->connection, $this->getTableName(), $where, [
            'field'  => $field,
        ]);
        $result = $query->execute();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $distinct[] = $row['value'];
        }

        return $distinct;
    }

    public function find($where = null, array $options = []): Result
    {
        $query = new Select($this->connection, $this->getTableName(), $where, $options);
        $result = $query->execute();

        return new Result($result);
    }

    public function findOne($where, array $options = [])
    {
        $options['limit'] = 1;
        $result = $this->find($where, $options);

        return $result->fetch();
    }

    public function findAll($where, array $options): array
    {
        return $this->find($where, $options)->fetchAll();
    }

    public function delete($where)
    {
        $query = new Delete($this->connection, $this->getTableName(), $where);
        $query->execute();
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