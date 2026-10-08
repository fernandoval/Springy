<?php

/**
 * Test case for Security\Remember\LazyRememberTokenStorage class.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

require_once __DIR__ . '/RememberTokenStorageTestCase.php';
require_once __DIR__ . '/InMemoryRememberTokenStorage.php';

use PHPUnit\Framework\Attributes\DataProvider;
use Springy\Security\Remember\LazyRememberTokenStorage;
use Springy\Security\Remember\RememberTokenManager;
use Springy\Security\Remember\RememberTokenStorageException;
use Springy\Security\Remember\RememberTokenStorageInterface;

class LazyRememberTokenStorageTest extends RememberTokenStorageTestCase
{
    private int $factoryCalls = 0;

    protected function createStorage(): RememberTokenStorageInterface
    {
        return $this->createLazyStorage(new InMemoryRememberTokenStorage());
    }

    private function createLazyStorage(mixed $result): LazyRememberTokenStorage
    {
        return new LazyRememberTokenStorage(function () use ($result) {
            $this->factoryCalls++;

            if ($result instanceof Throwable) {
                throw $result;
            }

            return $result;
        });
    }

    public function testThatFactoryIsNotCalledOnConstruction()
    {
        $this->createLazyStorage(new InMemoryRememberTokenStorage());

        $this->assertSame(0, $this->factoryCalls);
    }

    public function testThatManagerConstructionDoesNotCreateTheStorage()
    {
        $storage = $this->createLazyStorage(new InMemoryRememberTokenStorage());
        $manager = new RememberTokenManager($storage, 3600);

        $this->assertSame($storage, $manager->getStorage());
        $this->assertSame(0, $this->factoryCalls);
    }

    public function testThatFactoryIsCalledOnlyOnce()
    {
        $inner = new InMemoryRememberTokenStorage();
        $storage = $this->createLazyStorage($inner);
        $token = $this->createToken('42');

        $storage->save($token);
        $storage->findBySelector($token->selector);
        $storage->delete($token->selector);
        $storage->deleteAllByIdentity('42');

        $this->assertSame(1, $this->factoryCalls);
        $this->assertSame($inner, $storage->getStorage());
        $this->assertSame(1, $this->factoryCalls);
    }

    public function testThatSaveIsDelegated()
    {
        $inner = new InMemoryRememberTokenStorage();
        $storage = $this->createLazyStorage($inner);
        $token = $this->createToken('42');

        $storage->save($token);

        $this->assertSame([$token->selector => $token], $inner->tokens);
    }

    public function testThatFindBySelectorIsDelegated()
    {
        $inner = new InMemoryRememberTokenStorage();
        $token = $this->createToken('42');
        $inner->save($token);
        $storage = $this->createLazyStorage($inner);

        $this->assertSame($token, $storage->findBySelector($token->selector));
        $this->assertNull($storage->findBySelector('unknown'));
    }

    public function testThatDeleteIsDelegated()
    {
        $inner = new InMemoryRememberTokenStorage();
        $token = $this->createToken('42');
        $inner->save($token);
        $storage = $this->createLazyStorage($inner);

        $this->assertTrue($storage->delete($token->selector));
        $this->assertFalse($storage->delete($token->selector));
        $this->assertSame([], $inner->tokens);
    }

    public function testThatDeleteAllByIdentityIsDelegated()
    {
        $inner = new InMemoryRememberTokenStorage();
        $kept = $this->createToken('43');
        $inner->save($this->createToken('42'));
        $inner->save($this->createToken('42'));
        $inner->save($kept);
        $storage = $this->createLazyStorage($inner);

        $storage->deleteAllByIdentity('42');

        $this->assertSame([$kept->selector => $kept], $inner->tokens);
    }

    public function testThatStorageExceptionFromFactoryIsRethrownUnchanged()
    {
        $exception = new RememberTokenStorageException('Redis unavailable.');
        $storage = $this->createLazyStorage($exception);

        try {
            $storage->findBySelector('selector');
            $this->fail('RememberTokenStorageException was not thrown.');
        } catch (RememberTokenStorageException $thrown) {
            $this->assertSame($exception, $thrown);
        }
    }

    public function testThatOtherFactoryFailuresAreWrapped()
    {
        $exception = new RuntimeException('Connection refused.');
        $storage = $this->createLazyStorage($exception);

        try {
            $storage->save($this->createToken('42'));
            $this->fail('RememberTokenStorageException was not thrown.');
        } catch (RememberTokenStorageException $thrown) {
            $this->assertSame('Could not create the remember token storage.', $thrown->getMessage());
            $this->assertSame($exception, $thrown->getPrevious());
        }
    }

    public function testThatErrorsFromFactoryAreWrapped()
    {
        $exception = new Error('Class "Redis" not found.');
        $storage = $this->createLazyStorage($exception);

        try {
            $storage->delete('selector');
            $this->fail('RememberTokenStorageException was not thrown.');
        } catch (RememberTokenStorageException $thrown) {
            $this->assertSame($exception, $thrown->getPrevious());
        }
    }

    public function testThatFailedFactoryIsCalledAgainOnNextUse()
    {
        $inner = new InMemoryRememberTokenStorage();
        $attempts = 0;
        $storage = new LazyRememberTokenStorage(function () use ($inner, &$attempts) {
            if (++$attempts === 1) {
                throw new RuntimeException('Temporary failure.');
            }

            return $inner;
        });

        try {
            $storage->getStorage();
            $this->fail('RememberTokenStorageException was not thrown.');
        } catch (RememberTokenStorageException) {
        }

        $this->assertSame($inner, $storage->getStorage());
        $this->assertSame($inner, $storage->getStorage());
        $this->assertSame(2, $attempts);
    }

    #[DataProvider('invalidFactoryResultProvider')]
    public function testThatInvalidFactoryResultIsRejected(mixed $result)
    {
        $storage = $this->createLazyStorage($result);

        $this->expectException(RememberTokenStorageException::class);
        $this->expectExceptionMessage(
            'The remember token storage factory must return a ' . RememberTokenStorageInterface::class . '.'
        );

        $storage->getStorage();
    }

    public static function invalidFactoryResultProvider(): array
    {
        return [
            'null' => [null],
            'string' => ['storage'],
            'array' => [[]],
            'object' => [new stdClass()],
        ];
    }
}
