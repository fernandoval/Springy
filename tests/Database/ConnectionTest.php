<?php

/**
 * Test case for Springy\Database\Connection class.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2025 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

use PHPUnit\Framework\TestCase;
use Springy\Database\Connection;

class ConnectionTest extends TestCase
{
    public function testConnectionMySQL()
    {
        $connection = new Connection('mysql');
        $this->assertTrue($connection->isConnected());
        $this->assertEquals('`test`', $connection->enclose('test'));
        $this->assertEquals('*', $connection->enclose('*'));

        $sql = 'DROP TABLE IF EXISTS `test_spf`';

        $connection->run($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS `test_spf` ('
            . '`id` INT NOT NULL AUTO_INCREMENT, '
            . '`name` VARCHAR(20) NULL, '
            . '`created` DATETIME NOT NULL, '
            . '`deleted` TINYINT(1) NOT NULL DEFAULT \'0\', '
            . 'PRIMARY KEY (`id`))';

        $connection->run($sql);
        $connection->run('TRUNCATE TABLE `test_spf`');

        $result = $connection->execute(
            'INSERT INTO `test_spf`(`name`,`created`) '
            . 'VALUES (?, NOW()), (?, NOW()), (?, NOW()), (?, NOW()), (?, NOW()), (?, NOW()), (?, NOW())',
            ['Homer', 'Marge', 'Lisa', 'Bart', 'Meggy', 'Santa\'\'s Helper', 'Cat']
        );
        $this->assertEquals(7, $result);

        $result = $connection->select(
            'SELECT `id`, `name` FROM `test_spf` WHERE `id` BETWEEN ? AND ? ORDER BY `id`',
            [2, 5]
        );
        $this->assertCount(4, $result);
        $this->assertEquals(4, $connection->affectedRows());

        $row = $connection->getFirst();
        $this->assertEquals('Marge', $row['name'] ?? null);

        $row = $connection->getNext();
        $this->assertEquals('Lisa', $row['name'] ?? null);

        $row = $connection->getPrev();
        $this->assertEquals('Marge', $row['name'] ?? null);

        $row = $connection->fetch();
        $this->assertEquals(2, $row['id'] ?? null);

        $row = $connection->getLast();
        $this->assertEquals('Meggy', $row['name'] ?? null);

        $row = $connection->getCurrent();
        $this->assertEquals(5, $row['id'] ?? null);

        $result = $connection->execute('UPDATE `test_spf` SET `name` = ? WHERE `id` = ?', ['Grampa', 6]);
        $this->assertEquals(1, $result);

        $result = $connection->execute('DELETE FROM `test_spf` WHERE `id` = ?', [7]);
        $this->assertEquals(1, $result);
    }
}
