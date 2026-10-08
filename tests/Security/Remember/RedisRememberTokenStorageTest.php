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

        $selectorTtl = $this->redis->ttl(self::PREFIX . 'selector:' . $token->selector);
        $tokensTtl = $this->redis->ttl(self::PREFIX . 'tokens:' . $token->identityId);

        $this->assertGreaterThan(110, $selectorTtl);
        $this->assertLessThanOrEqual(120, $selectorTtl);
        $this->assertGreaterThan(110, $tokensTtl);

        $this->storage->deleteAllByIdentity($token->identityId);
    }

    public function testThatExpiredTokenIsNotSaved()
    {
        $token = $this->createToken($this->createIdentityId(), '-1 second');
        $this->storage->save($token);

        $this->assertNull($this->storage->findBySelector($token->selector));
    }

    public function testThatDeleteRemovesTheTokenKeys()
    {
        $token = $this->createToken($this->createIdentityId());
        $this->storage->save($token);
        $this->storage->delete($token->selector);

        $this->assertSame([], $this->redis->hKeys(self::PREFIX . 'tokens:' . $token->identityId));
        $this->assertSame(0, $this->redis->exists(self::PREFIX . 'selector:' . $token->selector));
    }

    public function testThatDeleteAllByIdentityRemovesTheTokenKeys()
    {
        $identityId = $this->createIdentityId();
        $first = $this->createToken($identityId);
        $second = $this->createToken($identityId);
        $this->storage->save($first);
        $this->storage->save($second);

        $this->storage->deleteAllByIdentity($identityId);

        // One key per EXISTS, because the keys may live in different cluster hash slots.
        $this->assertSame(0, $this->redis->exists(self::PREFIX . 'tokens:' . $identityId));
        $this->assertSame(0, $this->redis->exists(self::PREFIX . 'selector:' . $first->selector));
        $this->assertSame(0, $this->redis->exists(self::PREFIX . 'selector:' . $second->selector));
    }

    public function testThatTokenIsRevokedWhenTheIdentityHashIsGone()
    {
        $token = $this->createToken($this->createIdentityId());
        $this->storage->save($token);

        // Same state as a revocation that ran between writing the hash field and the selector pointer.
        $this->redis->del(self::PREFIX . 'tokens:' . $token->identityId);

        $this->assertNull($this->storage->findBySelector($token->selector));
        $this->assertFalse($this->storage->delete($token->selector));

        $this->redis->del(self::PREFIX . 'selector:' . $token->selector);
    }

    public function testThatOnlyTheValidatorHashIsStored()
    {
        $token = $this->createToken($this->createIdentityId());
        $this->storage->save($token);

        $payload = json_decode(
            $this->redis->hGet(self::PREFIX . 'tokens:' . $token->identityId, $token->selector),
            true
        );

        $this->assertSame($token->validatorHash, $payload['validator_hash']);

        $this->storage->deleteAllByIdentity($token->identityId);
    }
}
