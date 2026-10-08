<?php

/**
 * Test case for the query retry after a lost connection.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

use PHPUnit\Framework\TestCase;
use Springy\Database\Connection;
use Springy\Database\Connectors\Connector;

class MemorySqliteConnector extends Connector
{
    public function getDsn(): string
    {
        return 'sqlite::memory:';
    }
}

/**
 * Connection whose next query execution fails as if the connection was lost.
 */
class LosingConnection extends Connection
{
    public bool $loseConnection = false;
    public int $executions = 0;

    protected function executeQuery(): void
    {
        $this->executions += 1;

        if ($this->loseConnection) {
            $this->loseConnection = false;
            // Same as the parent method does with any failure.
            $this->lastError = 'SQLSTATE[HY000]: General error: 2006 MySQL server has gone away';

            throw new PDOException($this->lastError);
        }

        parent::executeQuery();
    }

    public function isReadOnly(string $query): bool
    {
        return $this->isReadOnlyQuery($query);
    }
}

class LostConnectionRetryTest extends TestCase
{
    private const IDENTITY = 'phpunit_losing';

    private LosingConnection $connection;

    protected function setUp(): void
    {
        config_set('database.connections.' . self::IDENTITY, [
            'driver' => MemorySqliteConnector::class,
            'database' => 'memory',
        ]);

        $this->connection = new LosingConnection(self::IDENTITY);
    }

    protected function tearDown(): void
    {
        $this->connection->disconnect();
    }

    public function testReadOnlyQueryIsRetriedOnANewConnection(): void
    {
        $this->connection->loseConnection = true;

        $this->assertSame([['one' => 1]], $this->connection->select('SELECT 1 AS one'));
        $this->assertSame(2, $this->connection->executions);
        $this->assertSame('', $this->connection->getError());
    }

    public function testWriteIsNotRetried(): void
    {
        $this->connection->run('CREATE TABLE test_retry (id INTEGER)');
        $this->connection->executions = 0;
        $this->connection->loseConnection = true;

        try {
            $this->connection->execute('INSERT INTO test_retry (id) VALUES (1)');
            $this->fail('The write should not be retried.');
        } catch (PDOException $err) {
            $this->assertStringContainsString('server has gone away', $err->getMessage());
        }

        $this->assertSame(1, $this->connection->executions);
        $this->assertStringContainsString('server has gone away', $this->connection->getError());
        $this->assertFalse($this->connection->isConnected());

        // The next statement opens a new connection.
        $this->assertSame([['one' => 1]], $this->connection->select('SELECT 1 AS one'));
    }

    public function testQueryIsNotRetriedInsideATransaction(): void
    {
        $this->connection->beginTransaction();
        $this->connection->loseConnection = true;

        try {
            $this->connection->select('SELECT 1 AS one');
            $this->fail('The query should not be retried inside a transaction.');
        } catch (PDOException $err) {
            $this->assertStringContainsString('server has gone away', $err->getMessage());
        }

        $this->assertSame(1, $this->connection->executions);

        $this->connection->rollBack();
    }

    public function testReadOnlyQueryDetection(): void
    {
        $readOnly = [
            'SELECT 1',
            "  select * from t\nwhere id = 1",
            '(SELECT a FROM t) UNION (SELECT a FROM u)',
            '/* comment */ SELECT 1',
            "-- comment\nSELECT 1",
            "# comment\nSELECT 1",
            'SHOW TABLES',
            'DESCRIBE t',
            'DESC t',
        ];
        $writes = [
            'INSERT INTO t VALUES (1)',
            'UPDATE t SET a = 1',
            'DELETE FROM t',
            'REPLACE INTO t VALUES (1)',
            'CREATE TABLE t (id INT)',
            'CALL proc()',
            'WITH x AS (DELETE FROM t RETURNING *) SELECT * FROM x',
            'SELECT * INTO new_t FROM t',
            "SELECT * FROM t INTO OUTFILE '/tmp/t'",
            'EXPLAIN ANALYZE DELETE FROM t',
            'SELECTED',
            '',
        ];

        foreach ($readOnly as $query) {
            $this->assertTrue($this->connection->isReadOnly($query), $query);
        }

        foreach ($writes as $query) {
            $this->assertFalse($this->connection->isReadOnly($query), $query);
        }
    }
}
