<?php

/**
 * Test case for Security\Remember\RedisRememberTokenStorage class.
 *
 * Requires the redis extension and a Redis or Valkey server at REDIS_HOST.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

require_once __DIR__ . '/RememberTokenStorageTestCase.php';

use Springy\Security\Remember\RedisRememberTokenStorage;
use Springy\Security\Remember\RememberTokenStorageInterface;

class RedisRememberTokenStorageTest extends RememberTokenStorageTestCase
{
    private const PREFIX = 'springy:test:remember:';

    private Redis $redis;

    protected function createStorage(): RememberTokenStorageInterface
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('The redis extension is not loaded.');
        }

        $host = getenv('REDIS_HOST');

        if ($host === false || $host === '') {
            $this->markTestSkipped('REDIS_HOST environment variable is not defined.');
        }

        $this->redis = new Redis();
        $this->redis->connect($host, (int) (getenv('REDIS_PORT') ?: 6379), 2.0);

        return new RedisRememberTokenStorage($this->redis, self::PREFIX);
    }

    public function testThatKeysExpireWithTheToken()
    {
        $token = $this->createToken($this->createIdentityId(), '+120 seconds');
        $this->storage->save($token);

        $tokenTtl = $this->redis->ttl(self::PREFIX . 'token:' . $token->selector);
        $identityTtl = $this->redis->ttl(self::PREFIX . 'identity:' . $token->identityId);

        $this->assertGreaterThan(110, $tokenTtl);
        $this->assertLessThanOrEqual(120, $tokenTtl);
        $this->assertGreaterThan(110, $identityTtl);

        $this->storage->deleteAllByIdentity($token->identityId);
    }

    public function testThatExpiredTokenIsNotSaved()
    {
        $token = $this->createToken($this->createIdentityId(), '-1 second');
        $this->storage->save($token);

        $this->assertNull($this->storage->findBySelector($token->selector));
    }

    public function testThatDeleteRemovesTheSelectorFromTheIdentityIndex()
    {
        $token = $this->createToken($this->createIdentityId());
        $this->storage->save($token);
        $this->storage->delete($token->selector);

        $this->assertSame([], $this->redis->sMembers(self::PREFIX . 'identity:' . $token->identityId));
    }

    public function testThatOnlyTheValidatorHashIsStored()
    {
        $token = $this->createToken($this->createIdentityId());
        $this->storage->save($token);

        $payload = json_decode($this->redis->get(self::PREFIX . 'token:' . $token->selector), true);

        $this->assertSame($token->validatorHash, $payload['validator_hash']);

        $this->storage->deleteAllByIdentity($token->identityId);
    }
}
