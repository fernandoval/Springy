<?php

/**
 * Contract tests shared by every "remember me" token storage driver.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

require_once __DIR__ . '/InterleavedRememberTokenStorage.php';

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

    public function testThatDeletingUnknownSelectorReturnsFalse()
    {
        $this->assertFalse($this->storage->delete(RememberTokenCredential::generate()->selector));
    }

    public function testThatOnlyTheFirstDeleteOfATokenReturnsTrue()
    {
        $token = $this->createToken($this->createIdentityId());
        $this->storage->save($token);

        $this->assertTrue($this->storage->delete($token->selector));
        $this->assertFalse($this->storage->delete($token->selector));
    }

    public function testThatDeletingARevokedTokenReturnsFalse()
    {
        $token = $this->createToken($this->createIdentityId());
        $this->storage->save($token);
        $this->storage->deleteAllByIdentity($token->identityId);

        $this->assertFalse($this->storage->delete($token->selector));
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

    /**
     * Rotates the cookie while revokeAllFor() runs right before the given storage operation.
     *
     * The rotation must fail and leave the identity without any valid token.
     */
    private function assertRevokeAllWinsOverRotation(string $operation): void
    {
        $identityId = $this->createIdentityId();
        $storage = new InterleavedRememberTokenStorage($this->storage);
        $manager = new RememberTokenManager($storage, 3600);
        $cookieValue = $manager->issue($identityId)->toString();

        $storage->before($operation, fn () => $this->storage->deleteAllByIdentity($identityId));

        try {
            $manager->rotate($cookieValue);
            $this->fail('The rotation bypassed the revocation.');
        } catch (InvalidRememberTokenException $exception) {
            $this->assertStringContainsString('not found', $exception->getMessage());
        }

        $this->assertCount(2, $storage->savedSelectors);

        foreach ($storage->savedSelectors as $selector) {
            $this->assertNull($this->storage->findBySelector($selector));
        }

        $this->storage->deleteAllByIdentity($identityId);
    }

    public function testThatRevokeAllBeforeRotationIssuesTheNewTokenWins()
    {
        $this->assertRevokeAllWinsOverRotation('save');
    }

    public function testThatRevokeAllBeforeRotationConsumesTheOldTokenWins()
    {
        $this->assertRevokeAllWinsOverRotation('delete');
    }

    public function testThatRevokeAllAfterRotationRevokesTheNewToken()
    {
        $identityId = $this->createIdentityId();
        $manager = new RememberTokenManager($this->storage, 3600);
        $cookieValue = $manager->issue($identityId)->toString();

        [$rotatedIdentityId, $credential] = $manager->rotate($cookieValue);
        $this->assertSame($identityId, $rotatedIdentityId);
        $this->assertSame($identityId, $manager->validate($credential->toString()));

        $manager->revokeAllFor($identityId);

        $this->assertNull($this->storage->findBySelector($credential->selector));
    }

    public function testThatConcurrentRotationsOfTheSameCookieHaveOneWinner()
    {
        $identityId = $this->createIdentityId();
        $storage = new InterleavedRememberTokenStorage($this->storage);
        $manager = new RememberTokenManager($storage, 3600);
        $cookieValue = $manager->issue($identityId)->toString();
        $winner = null;

        $storage->before('delete', function () use ($cookieValue, &$winner) {
            [, $winner] = (new RememberTokenManager($this->storage, 3600))->rotate($cookieValue);
        });

        try {
            $manager->rotate($cookieValue);
            $this->fail('The same cookie was rotated twice.');
        } catch (InvalidRememberTokenException $exception) {
            $this->assertStringContainsString('not found', $exception->getMessage());
        }

        $this->assertInstanceOf(RememberTokenCredential::class, $winner);
        $this->assertSame($identityId, $manager->validate($winner->toString()));

        $this->storage->deleteAllByIdentity($identityId);
    }
}
