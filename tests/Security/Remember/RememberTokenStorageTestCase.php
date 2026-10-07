<?php

/**
 * Contract tests shared by every "remember me" token storage driver.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

use PHPUnit\Framework\TestCase;
use Springy\Security\Remember\InvalidRememberTokenException;
use Springy\Security\Remember\RememberToken;
use Springy\Security\Remember\RememberTokenCredential;
use Springy\Security\Remember\RememberTokenManager;
use Springy\Security\Remember\RememberTokenStorageInterface;

abstract class RememberTokenStorageTestCase extends TestCase
{
    protected RememberTokenStorageInterface $storage;

    abstract protected function createStorage(): RememberTokenStorageInterface;

    protected function setUp(): void
    {
        $this->storage = $this->createStorage();
    }

    protected function createToken(string $identityId, string $expiresAt = '+1 hour'): RememberToken
    {
        $credential = RememberTokenCredential::generate();

        return new RememberToken(
            selector: $credential->selector,
            validatorHash: $credential->getValidatorHash(),
            identityId: $identityId,
            expiresAt: new DateTimeImmutable($expiresAt),
        );
    }

    /**
     * Returns an identity id unique for the test run, so concurrent runs do not collide.
     */
    protected function createIdentityId(): string
    {
        return 'test-' . bin2hex(random_bytes(6));
    }

    public function testThatStorageImplementsTheInterface()
    {
        $this->assertInstanceOf(RememberTokenStorageInterface::class, $this->storage);
    }

    public function testThatSavedTokenCanBeFound()
    {
        $token = $this->createToken($this->createIdentityId());
        $this->storage->save($token);

        $found = $this->storage->findBySelector($token->selector);

        $this->assertInstanceOf(RememberToken::class, $found);
        $this->assertSame($token->toArray(), $found->toArray());
    }

    public function testThatUnknownSelectorReturnsNull()
    {
        $this->assertNull($this->storage->findBySelector(RememberTokenCredential::generate()->selector));
    }

    public function testThatDeletedTokenIsNotFound()
    {
        $token = $this->createToken($this->createIdentityId());
        $this->storage->save($token);

        $this->storage->delete($token->selector);

        $this->assertNull($this->storage->findBySelector($token->selector));
    }

    public function testThatDeletingUnknownSelectorDoesNotFail()
    {
        $this->storage->delete(RememberTokenCredential::generate()->selector);

        $this->addToAssertionCount(1);
    }

    public function testThatDeleteAllByIdentityRemovesOnlyThatIdentityTokens()
    {
        $identityId = $this->createIdentityId();
        $otherIdentityId = $this->createIdentityId();
        $first = $this->createToken($identityId);
        $second = $this->createToken($identityId);
        $other = $this->createToken($otherIdentityId);

        foreach ([$first, $second, $other] as $token) {
            $this->storage->save($token);
        }

        $this->storage->deleteAllByIdentity($identityId);

        $this->assertNull($this->storage->findBySelector($first->selector));
        $this->assertNull($this->storage->findBySelector($second->selector));
        $this->assertNotNull($this->storage->findBySelector($other->selector));

        $this->storage->deleteAllByIdentity($otherIdentityId);
    }

    public function testThatNewTokensAreValidAfterDeleteAllByIdentity()
    {
        $identityId = $this->createIdentityId();
        $this->storage->save($this->createToken($identityId));
        $this->storage->deleteAllByIdentity($identityId);

        $token = $this->createToken($identityId);
        $this->storage->save($token);

        $this->assertNotNull($this->storage->findBySelector($token->selector));

        $this->storage->deleteAllByIdentity($identityId);
    }

    public function testThatDeleteAllByUnknownIdentityDoesNotFail()
    {
        $this->storage->deleteAllByIdentity($this->createIdentityId());

        $this->addToAssertionCount(1);
    }

    public function testThatManagerWorksWithTheStorage()
    {
        $identityId = $this->createIdentityId();
        $manager = new RememberTokenManager($this->storage, 3600);
        $cookieValue = $manager->issue($identityId)->toString();

        $this->assertSame($identityId, $manager->validate($cookieValue));

        $manager->revokeAllFor($identityId);

        $this->expectException(InvalidRememberTokenException::class);

        $manager->validate($cookieValue);
    }
}
