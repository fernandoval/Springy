<?php

/**
 * Test case for Security\Remember\MemcachedRememberTokenStorage class.
 *
 * Requires the memcached extension and a MemcacheD server at MEMCACHED_HOST.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

require_once __DIR__ . '/RememberTokenStorageTestCase.php';

use Springy\Security\Remember\MemcachedRememberTokenStorage;
use Springy\Security\Remember\RememberTokenStorageException;
use Springy\Security\Remember\RememberTokenStorageInterface;

class MemcachedRememberTokenStorageTest extends RememberTokenStorageTestCase
{
    private const PREFIX = 'springy.test.remember.';

    private Memcached $memcached;

    protected function createStorage(): RememberTokenStorageInterface
    {
        if (!extension_loaded('memcached')) {
            $this->markTestSkipped('The memcached extension is not loaded.');
        }

        $host = getenv('MEMCACHED_HOST');

        if ($host === false || $host === '') {
            $this->markTestSkipped('MEMCACHED_HOST environment variable is not defined.');
        }

        $this->memcached = new Memcached();
        $this->memcached->addServer($host, (int) (getenv('MEMCACHED_PORT') ?: 11211));

        return new MemcachedRememberTokenStorage($this->memcached, self::PREFIX);
    }

    public function testThatTokensLongerThanThirtyDaysAreKept()
    {
        $token = $this->createToken($this->createIdentityId(), '+60 days');
        $this->storage->save($token);

        $this->assertNotNull($this->storage->findBySelector($token->selector));

        $this->storage->deleteAllByIdentity($token->identityId);
    }

    public function testThatEvictedGenerationInvalidatesTheTokens()
    {
        $token = $this->createToken($this->createIdentityId());
        $this->storage->save($token);

        $this->memcached->delete(self::PREFIX . 'generation.' . hash('sha256', $token->identityId));

        $this->assertNull($this->storage->findBySelector($token->selector));
        $this->assertFalse($this->memcached->get(self::PREFIX . 'token.' . $token->selector));
    }

    public function testThatIdentityIdWithSpecialCharsIsAccepted()
    {
        $token = $this->createToken("user name\twith spaces " . bin2hex(random_bytes(4)));
        $this->storage->save($token);

        $this->assertNotNull($this->storage->findBySelector($token->selector));

        $this->storage->deleteAllByIdentity($token->identityId);
    }

    public function testThatUnreachableServerThrowsStorageException()
    {
        $memcached = new Memcached();
        $memcached->setOption(Memcached::OPT_CONNECT_TIMEOUT, 100);
        $memcached->addServer('127.0.0.1', 1);
        $storage = new MemcachedRememberTokenStorage($memcached, self::PREFIX);

        $this->expectException(RememberTokenStorageException::class);

        $storage->findBySelector(str_repeat('a', 24));
    }
}
