<?php
namespace PDOWrapper;

use PDO;
use PDOStatement;
use PDOWrapper\Collections\DB as ColDB;

class DBTest extends \PHPUnit_Framework_TestCase
{
    public function testSelect()
    {
        $sql = 'SELECT * FROM users WHERE id = ?';

        $statement = $this->prophesize(PDOStatement::class);
        $statement->execute([13])->shouldBeCalled();
        $statement->fetchAll(PDO::FETCH_ASSOC)->willReturn(['name' => 'User'])->shouldBeCalled();

        $pdo = $this->prophesize(PDO::class);
        $pdo->prepare($sql)->willReturn($statement->reveal());

        $db = new DB($pdo->reveal());

        $this->assertEquals(['name' => 'User'], $db->select($sql, 13));
    }
}
