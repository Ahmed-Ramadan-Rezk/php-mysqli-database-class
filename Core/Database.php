<?php

declare(strict_types=1);

namespace Core;

use mysqli;

class Database
{

    private static $conn;
    // hold name of table
    private string $table;
    // hold query 
    private string $query;
    // hold result of query
    private object $result;
    // store any results of query
    private array $data;
    // hold all conditions 
    private array $where;
    // for order by or limit or 
    private array $additionalParts;
    // define static value for sorting
    const SORTING = "DESC";


    public function __construct(
        private string $host = '',
        private string $username = '',
        private string $password = '',
        private string $database = ''
    ) {
        $this->connection();
    }

    // connection 
    public function connection(): object
    {

        if (!self::$conn) {
            self::$conn = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->database
                );
            self::$conn->set_charset("utf8");
        }

        return self::$conn;
    }

    // define the table 
    public function table(string $name): object
    {
        $this->table = $name;
        return $this;
    }

    
    // define first condition
    public function where(string $name, string $op, string $value): object
    {
        $value = self::$conn->real_escape_string($value);
        $this->where[] = " WHERE  `{$name}` {$op} '{$value}' ";
        return $this;
    }


    // add new condition with AND key 
    public function whereAnd(string $name, string $op, string $value): object
    {
        $value = self::$conn->real_escape_string($value);
        $this->where[] = " AND `{$name}` {$op} '{$value}' ";
        return $this;
    }


    // add new condition claus with (OR) key
    public function whereOr(string $name, string $op, string $value): object
    {
        $value = self::$conn->real_escape_string($value);
        $this->where[] = " OR `{$name}` {$op} '{$value}' ";
        return $this;
    }


    // extract  conditions 
    private function whereSentence(): string
    {
        $where = '';
        foreach ($this->where as $val) {
            $where .= $val;
        }
        return $where;
    }


    // extract additional statements like ( order by or limit or another ...)
    public function additionalParts(): string
    {
        $additional = '';
        foreach ($this->additionalParts as $val) {
            $additional .= $val;
        }
        return $additional;
    }


    // insert new record
    public function insert(array $data): object
    {
        $fields = "";
        $values = "";
        $last = array_key_last($data);
        foreach ($data as $key => $value) {
            if ($last == $key) {
                $fields .= "`$key`";
                $value = self::$conn->real_escape_string($value);
                $values .= "'$value'";
            } else {
                $fields .= "`$key`,";
                $value = self::$conn->real_escape_string($value);
                $values .= "'$value',";
            }
        }

        $this->query = "INSERT INTO {$this->table} ({$fields}) VALUES({$values}) ";
        return $this;
    }


    // update record
    public function update(array $data): object
    {
        $fields = "";
        $last = array_key_last($data);
        foreach ($data as $key => $value) {
            if ($last == $key) {
                $value = self::$conn->real_escape_string($value);
                $fields .= " `{$key}`='$value'";
            } else {
                $value = self::$conn->real_escape_string($value);
                $fields .= " `{$key}`='$value',";
            }
        }

        $this->query = "UPDATE {$this->table} SET {$fields} {$this->whereSentence()} ";
        return $this;
    }


    // get all data 
    public function get(): array|string
    {
        // extract where 
        $this->query = "SELECT * FROM {$this->table} {$this->whereSentence()} {$this->additionalParts()} ";
        if ($this->save()) {
            return $this->toArray();
        } else {
            return $this->queryError();
        }
    }


    // get one row
    public function find(mixed $data = null): array|string
    {
        if (is_int($data) && empty($this->where)) {
            $this->where = [" WHERE `id`='$data' "];
        }
        if (is_array($data) && empty($this->where)) {
            $this->where[] = [" WHERE `$data[0]` $data[1] '$data[2]'"];
        }
        $this->query = "SELECT * FROM {$this->table}   {$this->whereSentence()} ";
        if ($this->save()) {
            return $this->toArray();
        } else {
            return $this->queryError();
        }
    }


    // delete from database
    public function delete(string $id, string $field = 'id'): bool
    {
        $this->query = "DELETE FROM {$this->table} WHERE `$field`='$id'";
        if ($this->save()) {
            return true;
        } else {
            return false;
        }
    }


    public function save(): bool
    {
        $this->result = self::$conn->query($this->query);
        if (self::$conn->affected_rows >= 0 && $this->result == true) {
            return true;
        }
        return false;
    }


    // get error of query 
    public function queryError(): string
    {
        return self::$conn->error;
    }


    // get count 
    public function getNumRows(): int
    {
        return $this->result->num_rows;
    }


    // order by 
    public function orderBy(string $field, string $sort = self::SORTING): object
    {
        $this->additionalParts[] = " ORDER BY `{$field}` {$sort} ";
        return $this;
    }


    // limit
    public function limit(int $count, int $offset = 0): object
    {
        $this->additionalParts[] = " LIMIT {$offset} ,  {$count} ";
        return $this;
    }


    // get last inserted id 
    public function getLastId(): int
    {
        return self::$conn->insert_id;
    }


    // convert to array
    public function toArray(): array
    {
        while ($row = $this->result->fetch_object()) {
            $this->data[] = $row;
        }
        return $this->data;
    }
    

    // close the connection
    public function __destruct()
    {
        self::$conn->close();
    }
}
