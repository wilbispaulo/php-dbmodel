<?php

namespace Wilbispaulo\DBmodel;

use PDO;
use PDOException;
use Wilbispaulo\DBmodel\lib\DBConnection;
use Wilbispaulo\DBmodel\lib\DBFilters;
use Wilbispaulo\DBmodel\lib\DBPagination;

abstract class DBModel
{
    private mixed $fields = '*';
    private ?DBFilters $filters = null;
    private string $pagination = '';
    protected string $table;

    public function __construct(
        private $host,
        private $port,
        private $dbName,
        private $username,
        private $password
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

    public function create(array $valuesAssoc): bool
    {
        try {
            $fields = array_keys($valuesAssoc);
            $sql = "insert into {$this->table} (" . implode(', ', $fields) . ") values (:" . implode(', :', $fields) . ")";
            $connection = DBConnection::connect(
                $this->host,
                $this->port,
                $this->dbName,
                $this->username,
                $this->password
            );
            $prepare = $connection->prepare($sql);

            return $prepare->execute($valuesAssoc);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function fetchAllObj(): array|false
    {
        try {
            if (is_array($this->fields)) {
                $fields = implode(', ', $this->fields);
            } else {
                $fields = $this->fields;
            }
            $sql = "select {$fields} from {$this->table}{$this->filters?->dump()}{$this->pagination}";

            $connection = DBConnection::connect(
                $this->host,
                $this->port,
                $this->dbName,
                $this->username,
                $this->password
            );
            $prepare = $connection->prepare($sql);
            $prepare->execute(empty($this->filters) ? [] : $this->filters->getBind());
            return $prepare->fetchAll(PDO::FETCH_CLASS, get_called_class());
        } catch (PDOException $e) {
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
            $sql = "select {$fields} from {$this->table}{$this->filters?->dump()}{$this->pagination}";

            $connection = DBConnection::connect(
                $this->host,
                $this->port,
                $this->dbName,
                $this->username,
                $this->password
            );
            $prepare = $connection->prepare($sql);
            $prepare->execute(empty($this->filters) ? [] : $this->filters->getBind());
            return $prepare->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
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
                "select {$fields} from {$this->table} where {$field} = :{$field}" :
                "select {$fields} from {$this->table} {$this->filters?->dump()}";
            $connection = DBConnection::connect(
                $this->host,
                $this->port,
                $this->dbName,
                $this->username,
                $this->password
            );

            $prepare = $connection->prepare($sql);
            $prepare->execute(empty($this->filters) ? [$field => $value] : $this->filters->getBind());
            return $prepare->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function findByObj(string $field = '', mixed $value = ''): object|false
    {
        try {
            $sql = (empty($this->filters)) ?
                "select {$this->fields} from {$this->table} where {$field} = :{$field}" :
                "select {$this->fields} from {$this->table} {$this->filters?->dump()}";

            $connection = DBConnection::connect(
                $this->host,
                $this->port,
                $this->dbName,
                $this->username,
                $this->password
            );

            $prepare = $connection->prepare($sql);
            $prepare->execute(empty($this->filters) ? [$field => $value] : $this->filters->getBind());
            return $prepare->fetchObject(get_called_class());
        } catch (PDOException $e) {
            return false;
        }
    }

    // update users set firstName = :firstName, lastName = 'Prado', password = '8888' where id = 5
    public function update(array $fieldsValuesAssoc, string $fieldFilter = '', mixed $valueFilter = ''): bool
    {
        try {
            $sql = "update {$this->table} set";
            foreach ($fieldsValuesAssoc as $key => $valueAssoc) {
                $sql .= " {$key} = :{$key},";
                $valuesAssoc[":{$key}"] = $valueAssoc;
            }
            $sql = rtrim($sql, ",");
            if (empty($this->filters)) {
                $sql .= " where {$fieldFilter} = :{$fieldFilter}";
                $valuesAssoc[":{$fieldFilter}"] = $valueFilter;
            } else {
                $sql .= "{$this->filters?->dump()}";
                $valuesAssoc = array_merge($valuesAssoc, $this->filters->getBind());
            }

            $connection = DBConnection::connect(
                $this->host,
                $this->port,
                $this->dbName,
                $this->username,
                $this->password
            );

            $prepare = $connection->prepare($sql);

            return $prepare->execute($valuesAssoc);
        } catch (PDOException $e) {
            return false;
        }
    }

    // delete from users where id = 12
    public function delete(string $field = '', string|int $value = ''): bool
    {
        try {
            $sql = (empty($this->filters)) ?
                "delete from {$this->table} where {$field} = :{$field}" :
                "delete from {$this->table} {$this->filters?->dump()}";

            $connection = DBConnection::connect(
                $this->host,
                $this->port,
                $this->dbName,
                $this->username,
                $this->password
            );

            $prepare = $connection->prepare($sql);

            return $prepare->execute(empty($this->filters) ? [$field => $value] : $this->filters->getBind());
        } catch (PDOException $e) {
            return false;
        }
    }

    public function first(string $field, string $dir = 'asc'): object|false
    {
        try {
            $sql = "select {$this->fields} from {$this->table} order by {$field} {$dir} limit 1";

            $connection = DBConnection::connect(
                $this->host,
                $this->port,
                $this->dbName,
                $this->username,
                $this->password
            );

            $prepare = $connection->prepare($sql);

            $prepare->execute();

            return $prepare->fetchObject(get_called_class());
        } catch (PDOException $e) {
            return false;
        }
    }

    public function count(): mixed
    {
        try {
            $sql = "select count({$this->fields}) from {$this->table}{$this->filters?->dump()}";

            $connection = DBConnection::connect(
                $this->host,
                $this->port,
                $this->dbName,
                $this->username,
                $this->password
            );

            $prepare = $connection->prepare($sql);
            $prepare->execute(empty($this->filters) ? [] : $this->filters->getBind());

            return $prepare->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }
}
