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
  
$user = $db->selectRow('SELECT * FROM users WHERE id = ?', 13);
echo $user['name'];

$users = $db->select('SELECT * FROM users WHERE id IN (?,?,?)', [13, 14, 15])
    ->map(function (array $user) {
        return [
            'id' => $user['id'],
            'name' => $user['nickname'],
        ];
    });
    
$name = $db->selectCell('SELECT name FROM users WHERE id = 13');
```