<?php

namespace Serious\BoxDB;

use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use SQLite3;

class Database
{
    protected $connection;

    protected $dispatcher;

    public function __construct($filename, ?EventDispatcherInterface $dispatcher = null)
    {
        $this->connection = new SQLite3($filename);
        $this->connection->enableExceptions(true);
        $this->connection->createFunction('regexp', function ($pattern, $value) {
            return preg_match('('.$pattern.')', $value);
        });
        $this->connection->exec('CREATE TABLE IF NOT EXISTS box_collections (name TEXT PRIMARY KEY, options TEXT)');
        $this->dispatcher = $dispatcher;
    }

    /**
     * Creates a new collection.
     * 
     * @param string $name
     * @param array $options
     * @return Collection
     */
    public function createCollection(string $name, array $options = []): Collection
    {
        $stmt = $this->connection->prepare('INSERT INTO box_collections (name, options) VALUES (?, ?)');
        $stmt->bindValue(1, $name);
        $stmt->bindValue(2, json_encode($options));
        $stmt->execute();

        $sql = "PRAGMA foreign_keys = ON;
            CREATE TABLE box_collection_{$name} (
                _name TEXT NOT NULL,
                _path TEXT,
                _pathname TEXT NOT NULL,
                _created_at DATETIME,
                _updated_at DATETIME,
                document TEXT,
                PRIMARY KEY (_pathname),
                FOREIGN KEY (_path) REFERENCES box_collection_{$name} (_pathname) ON DELETE CASCADE
            );
            CREATE INDEX box_index_{$name}_created_at ON box_collection_{$name} (_created_at);
            CREATE INDEX box_index_{$name}_updated_at ON box_collection_{$name} (_updated_at);
            CREATE INDEX box_index_{$name}_name ON box_collection_{$name} (_name);";

        $this->connection->exec($sql);

        return new Collection($this->connection, $name, $options);
    }

    /**
     * Selects a collection from the database.
     * 
     * @param string $name
     * @return Collection
     */
    public function selectCollection($name): Collection
    {
        $stmt = $this->connection->prepare('SELECT * FROM box_collections WHERE name = ?');
        $stmt->bindValue(1, $name);
        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if ($result === false) {
            throw new RuntimeException(sprintf("Unkown collection '%s'", $name));
        }

        return new Collection($this->connection, $name, json_decode($result['options'], true));
    }

    /**
     * 
     */
    public function hasCollection($name): bool
    {
        $query = $this->connection->prepare('SELECT 1 FROM box_collections WHERE name = ?');
        $query->bindValue(1, $name);

        return (bool) $query->execute()->fetchArray(SQLITE3_NUM);
    }

    /**
     * Returns a list of collection objects from the database.
     * 
     * @return array
     */
    public function listCollections(): array
    {
        $collections = [];
        $result = $this->connection->query('SELECT * FROM box_collections');

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $collections[] = new Collection($row['name'], json_decode($row['options']));
        }

        return $collections;
    }

    /**
     * Returns names of all the collections in the database.
     *
     * @return array
     */
    public function listCollectionNames(): array
    {
        $collections = [];
        $result = $this->connection->query('SELECT name FROM box_collections');
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $collections[] = $row['name'];
        }

        return $collections;
    }

    /**
     * Drops a collection and all its data.
     * 
     * @param string $name
     */
    public function dropCollection($name)
    {
        $stmt = $this->connection->prepare('DELETE FROM box_collections WHERE name = ?');
        $stmt->bindValue(1, $name);
        $stmt->execute();

        $delete = sprintf('DROP TABLE IF EXISTS box_collection_%s', $name);
        $this->connection->exec($delete);
    }

    /**
     * Starts a transaction.
     */
    public function beginTransaction()
    {
        $this->connection->exec('BEGIN TRANSACTION');
    }

    /**
     * Commits a transaction.
     */
    public function commitTransaction()
    {
        $this->connection->exec('COMMIT TRANSACTION');
    }

    /**
     * Rolls back a transaction.
     */
    public function rollbackTransaction()
    {
        $this->connection->exec('ROLLBACK TRANSACTION');
    }

    public function createSavepoint(string $savepoint)
    {
        $this->connection->exec("SAVEPOINT {$savepoint}");
    }

    public function releaseSavepoint(string $savepoint)
    {
        $this->connection->exec("RELEASE SAVEPOINT {$savepoint}");
    }

    public function rollbackToSavepoint(string $savepoint)
    {
        $this->connection->exec("ROLLBACK TO SAVEPOINT {$savepoint}");
    }
}