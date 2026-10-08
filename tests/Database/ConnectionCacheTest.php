<?php

/**
 * Test case for the query cache of Springy\Database\Connection class.
 *
 * Requires the memcached extension and a MemcacheD server at MEMCACHED_HOST.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

use PHPUnit\Framework\TestCase;
use Springy\Core\Debug;
use Springy\Database\Connection;
use Springy\Database\Connectors\SQLite;

class ConnectionCacheTest extends TestCase
{
    private const IDENTITY = 'phpunit_cache';

    private Connection $connection;
    private mixed $previousCache;
    private string $tag;

    protected function setUp(): void
    {
        if (!extension_loaded('memcached')) {
            $this->markTestSkipped('The memcached extension is not loaded.');
        }

        $host = getenv('MEMCACHED_HOST');

        if ($host === false || $host === '') {
            $this->markTestSkipped('MEMCACHED_HOST environment variable is not defined.');
        }

        $this->previousCache = config_get('database.cache');

        config_set('database.cache', [
            'driver' => 'memcached',
            'host' => $host,
            'port' => (int) (getenv('MEMCACHED_PORT') ?: 11211),
        ]);
        config_set('database.connections.' . self::IDENTITY, [
            'driver' => SQLite::class,
            'database' => ':memory:',
        ]);

        $this->connection = new Connection(self::IDENTITY);
        $this->connection->run('CREATE TABLE "cached" ("id" INTEGER, "tag" TEXT)');
        // A unique parameter avoids hitting entries cached by previous runs.
        $this->tag = uniqid('', true);
        $this->connection->run('INSERT INTO "cached" VALUES (1, ?)', [$this->tag]);
        $this->setDebugEntries([]);
    }

    protected function tearDown(): void
    {
        if (isset($this->connection)) {
            $this->connection->disconnect();
            config_set('database.cache', $this->previousCache ?? ['driver' => 'none']);
        }
    }

    public function testThatCacheMissStoresTheRowsWithoutErrors(): void
    {
        $query = 'SELECT "id", "tag" FROM "cached" WHERE "tag" = ?';
        $rows = $this->connection->select($query, [$this->tag], null, 60);

        $this->assertCount(1, $rows);
        $this->assertSame([], $this->getDebugEntries());

        // The rows were removed, so only the cache can return them now.
        $this->connection->run('DELETE FROM "cached"');

        $this->assertSame($rows, $this->connection->select($query, [$this->tag], null, 60));
    }

    private function getDebugEntries(): array
    {
        return (new ReflectionProperty(Debug::class, 'debug'))->getValue();
    }

    private function setDebugEntries(array $entries): void
    {
        (new ReflectionProperty(Debug::class, 'debug'))->setValue(null, $entries);
    }
}
