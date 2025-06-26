<?php

namespace Wilbispaulo\DBmodel;

use Doctrine\DBAL\Exception;

class DBModel
{
    protected string $table;
    private mixed $fields = '*';
    private ?DBFilters $filters = null;
    private string $pagination = '';

    public function __construct(
        private $dbName,
        private $user,
        private $password,
        private $host,
        private $driver
    ) {}

    public function getTable()
    {
        return $this->table;
    }

    public function setFields(mixed $fields)
    {
        $this->fields = $fields;
    }

    public function setFilters(DBFilters $filters)
    {
        $this->filters = $filters;
    }

    public function setPagination(DBPagination $pagination)
    {
        $pagination->setTotalItens($this->count());
        $this->pagination = $pagination->dump();
    }

    public function create(array $valuesAssoc): int|string|false
    {
        try {
            $connection = new DBConnection(
                $this->dbName,
                $this->user,
                $this->password,
                $this->host,
                $this->driver,
            )->Connect();
            return $connection->insert($this->table, $valuesAssoc);
        } catch (Exception $e) {
            return false;
        }
    }

    public function fetchAll(): array|false
    {
        try {
            if (is_array($this->fields)) {
                $fields = implode(', ', $this->fields);
            } else {
                $fields = $this->fields;
            }
            $sql = "SELECT {$fields} FROM {$this->table}{$this->filters?->dump()}{$this->pagination}";

            $connection = new DBConnection(
                $this->dbName,
                $this->user,
                $this->password,
                $this->host,
                $this->driver,
            )->Connect();
            $stmt = $connection->executeQuery($sql, empty($this->filters) ? [] : $this->filters->getBind());
            return $stmt->fetchAllAssociative();
        } catch (Exception $e) {
            return false;
        }
    }

    public function findBy(string $field = '', mixed $value = ''): array|false
    {
        try {
            if (is_array($this->fields)) {
                $fields = implode(', ', $this->fields);
            } else {
                $fields = $this->fields;
            }

            $sql = (empty($this->filters)) ?
                "SELECT {$fields} FROM {$this->table} WHERE {$field} = :{$field}" :
                "SELECT {$fields} FROM {$this->table} {$this->filters?->dump()}";
            $connection = new DBConnection(
                $this->dbName,
                $this->user,
                $this->password,
                $this->host,
                $this->driver,
            )->Connect();
            $stmt = $connection->executeQuery($sql, empty($this->filters) ? [$field => $value] : $this->filters->getBind());
            return $stmt->fetchAllAssociative();
        } catch (Exception $e) {
            return false;
        }
    }

    // update users set firstName = :firstName, lastName = 'Prado', password = '8888' where id = 5
    public function update(array $fieldsValuesAssoc, string $fieldFilter = '', mixed $valueFilter = ''): int|string|false
    {
        try {
            $sql = "UPDATE {$this->table} SET";
            foreach ($fieldsValuesAssoc as $key => $valueAssoc) {
                $sql .= " {$key} = :{$key},";
                $valuesAssoc[":{$key}"] = $valueAssoc;
            }
            $sql = rtrim($sql, ",");
            if (empty($this->filters)) {
                $sql .= " WHERE {$fieldFilter} = :{$fieldFilter}";
                $valuesAssoc[":{$fieldFilter}"] = $valueFilter;
            } else {
                $sql .= "{$this->filters?->dump()}";
                $valuesAssoc = array_merge($valuesAssoc, $this->filters->getBind());
            }

            $connection = new DBConnection(
                $this->dbName,
                $this->user,
                $this->password,
                $this->host,
                $this->driver,
            )->Connect();
            return $connection->executeStatement($sql, empty($this->filters) ? [] : $this->filters->getBind());
        } catch (Exception $e) {
            return false;
        }
    }

    // delete from users where id = 12
    public function delete(string $field = '', string|int $value = ''): int|string|false
    {
        try {
            $sql = (empty($this->filters)) ?
                "DELETE FROM {$this->table} WHERE {$field} = :{$field}" :
                "DELETE FROM {$this->table} {$this->filters?->dump()}";

            $connection = new DBConnection(
                $this->dbName,
                $this->user,
                $this->password,
                $this->host,
                $this->driver,
            )->Connect();
            return $connection->executeStatement($sql, empty($this->filters) ? [$field => $value] : $this->filters->getBind());
        } catch (Exception $e) {
            return false;
        }
    }

    public function first(string $field, string $dir = 'asc'): array|false
    {
        try {
            $sql = "SELECT {$this->fields} FROM {$this->table} ORDER BY {$field} {$dir} LIMIT 1";

            $connection = new DBConnection(
                $this->dbName,
                $this->user,
                $this->password,
                $this->host,
                $this->driver,
            )->Connect();
            $stmt = $connection->executeQuery($sql, empty($this->filters) ? [] : $this->filters->getBind());
            return $stmt->fetchAllAssociative();
        } catch (Exception $e) {
            return false;
        }
    }

    public function count(): mixed
    {
        try {
            $sql = "SELECT COUNT({$this->fields}) FROM {$this->table}{$this->filters?->dump()}";

            $connection = new DBConnection(
                $this->dbName,
                $this->user,
                $this->password,
                $this->host,
                $this->driver,
            )->Connect();
            return $connection->fetchOne($sql, empty($this->filters) ? [] : $this->filters->getBind());
        } catch (Exception $e) {
            return false;
        }
    }
}
