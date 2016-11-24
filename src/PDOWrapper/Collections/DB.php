<?php
namespace PDOWrapper\Collections;

use Illuminate\Support\Collection;
use PDOWrapper\DB as BaseDB;

class DB
{
    /**
     * @var BaseDB
     */
    private $db;

    public function __construct(BaseDB $db)
    {
        $this->db = $db;
    }

    public function select(string $sql, $params = [])
    {
        return new Collection($this->db->select($sql, $params));
    }

    public function selectColumn(string $sql, $params = [])
    {
        return new Collection($this->db->selectColumn($sql, $params));
    }

    public function selectWithKey($index, $sql, $params = [])
    {
        return new Collection($this->db->selectWithKey($index, $sql, $params));
    }
}
