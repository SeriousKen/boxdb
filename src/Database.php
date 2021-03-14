<?php

namespace Serious\BoxDB;

use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use SQLite3;
use Symfony\Component\VarDumper\VarDumper;

class Database
{
    protected $connection;

    protected $dispatcher;

    public function __construct($filename, ?EventDispatcherInterface $dispatcher = null)
    {
        $this->connection = new SQLite3($filename);
        $this->connection->enableExceptions(true);
        $this->dispatcher = $dispatcher;

        $this->connection->exec('CREATE TABLE IF NOT EXISTS box_collections (name TEXT PRIMARY KEY)');
    }

    public function createCollection($name): Collection
    {
        $stmt = $this->connection->prepare('INSERT OR IGNORE INTO box_collections (name) VALUES (?)');
        $stmt->bindValue(1, $name);
        $stmt->bindValue(2, $name);
        $stmt->execute();

        if ($this->connection->changes()) {
            $sql = "CREATE TABLE box_collection_{$name} (
                    _id TEXT,
                    _path TEXT,
                    _created_at DATETIME,
                    _updated_at DATETIME,
                    document TEXT,
                    PRIMARY KEY (_path, _id)
                );
                CREATE INDEX box_index_{$name}_created_at ON box_collection_{$name} (_created_at);
                CREATE INDEX box_index_{$name}_updated_at ON box_collection_{$name} (_updated_at);
                CREATE TRIGGER box_created_at_timestamp AFTER INSERT ON box_collection_{$name}
                FOR EACH ROW
                BEGIN
                    UPDATE box_collection_{$name} SET _created_at = strftime('%s', 'now'), _updated_at = strftime('%s', 'now') WHERE _id IS new._id AND _path IS new._path;
                END;
                CREATE TRIGGER box_updated_at_timestamp AFTER UPDATE OF document ON box_collection_{$name}
                FOR EACH ROW WHEN new.document <> old.document
                BEGIN
                    UPDATE box_collection_{$name} SET _updated_at = strftime('%s', 'now') WHERE _id IS new._id AND _path IS new._path;
                END;";

            $this->connection->exec($sql);
        }

        return new Collection($this->connection, $name);
    }

    public function selectCollection($name): Collection
    {
        return $this->createCollection($name);
    }

    public function hasCollection($name): bool
    {
        $query = $this->connection->prepare('SELECT 1 FROM box_collections WHERE name = ?');
        $query->bindValue(1, $name);

        return (bool) $query->execute()->fetchArray(SQLITE3_NUM)[0];
    }

    public function listCollectionNames(): array
    {
        $collections = [];
        $result = $this->connection->query('SELECT name FROM box_collections');
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $collections[] = $row['name'];
        }

        return $collections;
    }

    public function listCollections(): array
    {
        return array_map(function ($name) {
            return new Collection($this->connection, $name);
        }, $this->listCollectionNames());
    }

    public function dropCollection($name)
    {
        $stmt = $this->connection->prepare('DELETE FROM box_collections WHERE name = ?');
        $stmt->bindValue(1, $name);
        $stmt->execute();

        $delete = sprintf('DROP TABLE IF EXISTS box_collection_%s', $name);
        $this->connection->exec($delete);
    }

    public function beginTransaction()
    {
        $this->connection->exec('BEGIN TRANSACTION');
    }

    public function commitTransaction()
    {
        $this->connection->exec('COMMIT TRANSACTION');
    }

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