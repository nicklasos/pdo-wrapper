# Wrapper for PDO


```php
use PDOWrapper\DB;
use PDOWrapper\Collections;

$pdo = new Collections\DB(new DB(new \PDO(
   'mysql:dbname=dbname;host=localhost',
   'root',
   'password',
   [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'']
)));
  
$user = $db->select('SELECT * FROM users WHERE id = ?', 13);
$users = $db->select('SELECT * FROM users WHERE id IN (?,?,?)', [13, 14, 15])
    ->map(function (array $user) {
        return [
            'id' => $user['id'],
            'name' => $user['nickname'],
        ];
    });

```