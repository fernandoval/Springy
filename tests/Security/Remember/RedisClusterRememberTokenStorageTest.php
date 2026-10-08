<?php

/**
 * Test case for Security\Remember\RedisRememberTokenStorage class on a cluster.
 *
 * A cluster rejects multi-key operations spanning different hash slots
 * (CROSSSLOT), as AWS ElastiCache Serverless does. Requires the redis
 * extension and a Redis or Valkey Cluster node at REDIS_CLUSTER_HOST.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

require_once __DIR__ . '/RememberTokenStorageTestCase.php';

use Springy\Security\Remember\RedisRememberTokenStorage;
use Springy\Security\Remember\RememberTokenStorageInterface;

class RedisClusterRememberTokenStorageTest extends RememberTokenStorageTestCase
{
    protected function createStorage(): RememberTokenStorageInterface
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('The redis extension is not loaded.');
        }

        $host = getenv('REDIS_CLUSTER_HOST');

        if ($host === false || $host === '') {
            $this->markTestSkipped('REDIS_CLUSTER_HOST environment variable is not defined.');
        }

        $seed = $host . ':' . (getenv('REDIS_CLUSTER_PORT') ?: 6379);

        return new RedisRememberTokenStorage(
            new RedisCluster(null, [$seed], 2.0, 2.0),
            'springy:test:remember:'
        );
    }
}
