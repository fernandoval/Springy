<?php

/**
 * Test case for reconnection after a failed connection attempt.
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

/**
 * SQLite connector whose first connection attempts fail on demand.
 */
class FlakySqliteConnector extends Connector
{
    public static int $failures = 0;

    public function getDsn(): string
    {
        if (self::$failures > 0) {
            self::$failures -= 1;

            return 'invalid:dsn';
        }

        return 'sqlite::memory:';
    }
}

class FailedConnectionTest extends TestCase
{
    private const IDENTITY = 'phpunit_flaky';

    protected function setUp(): void
    {
        config_set('database.connections.' . self::IDENTITY, [
            'driver' => FlakySqliteConnector::class,
            'database' => 'memory',
        ]);
        FlakySqliteConnector::$failures = 0;
    }

    protected function tearDown(): void
    {
        FlakySqliteConnector::$failures = 0;
    }

    public function testConnectorPdoIsNullBeforeConnecting(): void
    {
        $connector = new FlakySqliteConnector(['database' => 'memory']);

        $this->assertNull($connector->getPdo());
    }

    public function testReconnectsAfterFailedConnection(): void
    {
        FlakySqliteConnector::$failures = 1;

        try {
            new Connection(self::IDENTITY);
            $this->fail('The first connection attempt should fail.');
        } catch (PDOException $err) {
            $this->assertNotEmpty($err->getMessage());
        }

        $connection = new Connection(self::IDENTITY);
        $connection->disconnect();

        FlakySqliteConnector::$failures = 1;

        try {
            $connection->connect();
            $this->fail('The connection attempt should fail.');
        } catch (PDOException $err) {
            $this->assertFalse($connection->isConnected());
        }

        $connection->connect();
        $this->assertTrue($connection->isConnected());
        $this->assertEquals([['one' => 1]], $connection->select('SELECT 1 AS one'));

        $connection->disconnect();
    }
}
