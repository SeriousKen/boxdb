<?php

namespace Serious\BoxDB;

use SQLite3;

class Database
{
    protected $connection;

    public function __construct($filename)
    {
        $this->connection = new SQLite3($filename);
        $this->connection->enableExceptions(true);
        $this->connection->exec("CREATE TABLE IF NOT EXISTS box_collections (name TEXT PRIMARY KEY)");
    }

    public function createCollection($name): Collection
    {
        $stmt = $this->connection->prepare("SELECT name FROM box_collections WHERE name = ?");
        $stmt->bindValue(1, $name);
        $result = $stmt->execute(); 

        if ($result->fetchArray(SQLITE3_ASSOC) === false) {
            $sql = sprintf('SAVEPOINT create_collection;
                INSERT INTO box_collections (name) VALUES (\'%s\');
                CREATE TABLE box_collection_%1$s (_id BLOB PRIMARY KEY, document TEXT);
                CREATE UNIQUE INDEX box_collection_%1$s__id ON box_collection_%1$s (json_extract(document, \'$._id\'));
                RELEASE create_collection;', $name);
            $this->connection->exec($sql);
        }

        return new Collection($this->connection, $name);
    }

    public function selectCollection($name): Collection
    {
        return $this->createCollection($name);
    }

    public function listCollections(): array
    {
        $collections = [];
        $stmt = $this->connection->prepare("SELECT name FROM box_collections");
        $result = $stmt->execute();
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $collections[] = new Collection($this->connection, $row['name']);
        }

        return $collections;
    }

    public function listCollectionNames()
    {
        $collections = [];
        $stmt = $this->connection->prepare("SELECT name FROM box_collections");
        $result = $stmt->execute();
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $collections[] = $row['name'];
        }

        return $collections;
    }

    public function dropCollection($name)
    {
        $sql = sprintf('SAVEPOINT drop_collection;
            DELETE FROM box_collections WHERE name = \'%s\';
            DROP TABLE IF EXISTS box_collection_%1$s;
            RELEASE drop_collection;', $name);
        $this->connection->exec($sql);
    }

    public function beginTransaction()
    {
        $this->connection->exec('BEGIN');
    }

    public function commitTransaction()
    {
        $this->connection->exec('COMMIT');
    }

    public function rollbackTransaction()
    {
        $this->connection->exec('ROLLBACK');
    }
}