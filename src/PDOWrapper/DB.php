<?php
namespace PDOWrapper;

use PDO;
use PDOStatement;

/**
 * @see https://github.com/panique/pdo-debug
 */

/**
 * Small helper for working with native PDO
 *
 * $pdo = new DB(new \PDO(
 *     "mysql:dbname=dbname;host=localhost",
 *     'root',
 *     'password',
 *     [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'']
 * ));
 *
 * $user = $db->select('SELECT * FROM users WHERE id = ?', 13);
 * $users = $db->select('SELECT * FROM users WHERE id IN (?,?,?)', [13, 14, 15]);
 *
 * @package Plariumed\Utils
 */
class DB
{
    /**
     * @var PDO
     */
    private $pdo;

    private $debug = [];

    /**
     * Db constructor.
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function debug(): array
    {
        return $this->debug;
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    public function transaction()
    {
        $this->pdo->beginTransaction();
    }

    public function commit()
    {
        $this->pdo->commit();
    }

    public function rollback()
    {
        $this->pdo->rollBack();
    }

    /**
     * @param string $sql
     * @param array|mixed $params
     * @return array
     */
    public function select(string $sql, $params = []): array
    {
        return $this->execute($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $sql
     * @param array|mixed $params
     * @return array
     */
    public function selectColumn(string $sql, $params = []): array
    {
        return $this->execute($sql, $params)->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @param string $sql
     * @param array|mixed $params
     * @return PDOStatement
     */
    public function execute(string $sql, $params = []): PDOStatement
    {
        if (!is_array($params)) {
            $params = [$params];
        }

        $statement = $this->pdo->prepare($sql);

        if ($this->isList($params)) {
            $statement->execute($params);
        } else {
            foreach ($params as $key => $param) {
                if (is_integer($param)) {
                    $statement->bindValue($key, $param, PDO::PARAM_INT);
                } else {
                    $statement->bindValue($key, $param);
                }
            }

            $statement->execute();
        }

        $this->logForDebug($sql, $params);

        return $statement;
    }

    /**
     * Return single row
     * @param string $sql
     * @param array $params
     * @return array|bool
     */
    public function selectRow($sql, $params = []): array
    {
        $row = $this->select($sql, $params);

        return $row[0] ?? [];
    }

    /**
     * Return one cell
     * @param string $sql
     * @param array $params
     * @return string|int|bool|null
     */
    public function selectCell($sql, $params = [])
    {
        $row = $this->selectRow($sql, $params);

        return (is_array($row) ? array_pop($row) : null);
    }

    /**
     * @param string $index
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function selectWithKey($index, $sql, $params = []): array
    {
        $select = $this->select($sql, $params);

        $result = [];
        foreach ($select as $row) {
            $result[$row[$index]] = $row;
        }

        return $result;
    }

    /**
     * $db->insert('table', ['field' => 'value']);
     *
     * @param string $table
     * @param array $params
     * @return PDOStatement
     */
    public function insert(string $table, array $params): PDOStatement
    {
        $columns = implode(', ', array_map(function ($column) {
            return "`$column`";
        }, array_keys($params)));

        $placeholders = implode(', ', array_fill(0, count($params), '?'));

        return $this->execute("INSERT INTO $table ($columns) values ($placeholders)", array_values($params));
    }

    /**
     * @param null $name
     * @return string
     */
    public function lastInsertId($name = null): string
    {
        return $this->pdo->lastInsertId($name);
    }

    /**
     * $db->update('table', ['field' => 'value'], ['id' => 1]);
     *
     * @param string $table
     * @param array $params
     * @param array $where
     * @return PDOStatement
     */
    public function update(string $table, array $params, array $where): PDOStatement
    {
        $sqlParams = $this->where($params, ', ');
        $whereParams = $this->where($where, ' AND ');

        $sql = "UPDATE {$table} SET {$sqlParams} WHERE {$whereParams}";

        $values = array_merge(array_values($params), array_values($where));

        return $this->execute($sql, $values);
    }

    /**
     * @param string $table
     * @param array $where
     * @return PDOStatement
     */
    public function delete(string $table, array $where): PDOStatement
    {
        $whereParams = $this->where($where, ' AND ');

        $sql = "DELETE FROM {$table} WHERE {$whereParams}";

        return $this->execute($sql, array_values($where));
    }

    /**
     * @param array $params
     * @param string $delimiter
     * @return string
     */
    private function where(array $params, string $delimiter): string
    {
        $sqlParams = [];
        foreach ($params as $name => $value) {
            $sqlParams[] = "`$name` = ?";
        }

        return implode($delimiter, $sqlParams);
    }

    /**
     * @param array $array
     * @return bool
     */
    private function isList(array $array): bool
    {
        return array_values($array) === $array;
    }

    /**
     * @param $sql
     * @param $params
     */
    private function logForDebug($sql, $params)
    {
        $keys = [];
        $values = [];
        $isNamedMarkers = false;

        if (count($params) && is_string(key($params))) {
            uksort($params, function ($k1, $k2) {
                return strlen($k2) - strlen($k1);
            });
            $isNamedMarkers = true;
        }

        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:' . ltrim($key, ':') . '/';
            } else {
                $keys[] = '/[?]/';
            }

            if (is_string($value)) {
                $values[] = "'" . addslashes($value) . "'";
            } elseif (is_int($value)) {
                $values[] = strval($value);
            } elseif (is_float($value)) {
                $values[] = strval($value);
            } elseif (is_array($value)) {
                $values[] = implode(',', $value);
            } elseif (is_null($value)) {
                $values[] = 'NULL';
            }
        }

        if ($isNamedMarkers) {
            $this->debug[] = preg_replace($keys, $values, $sql);
        } else {
            $this->debug[] = preg_replace($keys, $values, $sql, 1, $count);
        }
    }
}
