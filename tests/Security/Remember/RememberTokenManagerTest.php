<?php

/**
 * Test case for Security\Remember\RememberTokenManager class.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

require_once __DIR__ . '/InMemoryRememberTokenStorage.php';

use PHPUnit\Framework\TestCase;
use Springy\Security\Remember\InvalidRememberTokenException;
use Springy\Security\Remember\RememberToken;
use Springy\Security\Remember\RememberTokenCredential;
use Springy\Security\Remember\RememberTokenManager;

class RememberTokenManagerTest extends TestCase
{
    private InMemoryRememberTokenStorage $storage;
    private RememberTokenManager $manager;

    protected function setUp(): void
    {
        $this->storage = new InMemoryRememberTokenStorage();
        $this->manager = new RememberTokenManager($this->storage, 3600);
    }

    public function testThatLifetimeAndStorageAreExposed()
    {
        $this->assertSame(3600, $this->manager->getLifetime());
        $this->assertSame($this->storage, $this->manager->getStorage());
        $this->assertSame(
            RememberTokenManager::DEFAULT_LIFETIME,
            (new RememberTokenManager($this->storage))->getLifetime()
        );
    }

    public function testThatNonPositiveLifetimeIsRejected()
    {
        $this->expectException(InvalidArgumentException::class);

        new RememberTokenManager($this->storage, 0);
    }

    public function testThatIssueStoresOnlyTheValidatorHash()
    {
        $credential = $this->manager->issue('42');
        $token = $this->storage->findBySelector($credential->selector);

        $this->assertInstanceOf(RememberToken::class, $token);
        $this->assertSame('42', $token->identityId);
        $this->assertSame($credential->getValidatorHash(), $token->validatorHash);
        $this->assertStringNotContainsString($credential->validator, serialize($this->storage->tokens));
    }

    public function testThatIssuedTokenExpiresAfterLifetime()
    {
        $credential = $this->manager->issue('42');
        $seconds = $this->storage->findBySelector($credential->selector)->getSecondsToExpire();

        $this->assertGreaterThanOrEqual(3598, $seconds);
        $this->assertLessThanOrEqual(3600, $seconds);
    }

    public function testThatCookieValueDoesNotExposeTheIdentityId()
    {
        $credential = $this->manager->issue('user-identifier-42');

        $this->assertStringNotContainsString('user-identifier-42', $credential->toString());
    }

    public function testThatValidCookieReturnsTheIdentityId()
    {
        $credential = $this->manager->issue('42');

        $this->assertSame('42', $this->manager->validate($credential->toString()));
    }

    public function testThatRevokedCookieIsRejected()
    {
        $cookieValue = $this->manager->issue('42')->toString();
        $this->manager->revoke($cookieValue);

        $this->expectException(InvalidRememberTokenException::class);
        $this->expectExceptionMessage('not found');

        $this->manager->validate($cookieValue);
    }

    public function testThatRevokeIgnoresMalformedValues()
    {
        $this->manager->issue('42');
        $this->manager->revoke('not a token');

        $this->assertCount(1, $this->storage->tokens);
    }

    public function testThatRevokeAllForInvalidatesEveryDeviceOfTheIdentityOnly()
    {
        $desktop = $this->manager->issue('42')->toString();
        $phone = $this->manager->issue('42')->toString();
        $otherUser = $this->manager->issue('7')->toString();

        $this->manager->revokeAllFor('42');

        $this->assertSame('7', $this->manager->validate($otherUser));

        foreach ([$desktop, $phone] as $cookieValue) {
            try {
                $this->manager->validate($cookieValue);
                $this->fail('Revoked token was accepted.');
            } catch (InvalidRememberTokenException $exception) {
                $this->assertStringContainsString('not found', $exception->getMessage());
            }
        }
    }

    public function testThatLegacyCookieWithUserIdIsRejected()
    {
        $this->expectException(InvalidRememberTokenException::class);
        $this->expectExceptionMessage('Malformed');

        $this->manager->validate('42');
    }

    public function testThatExpiredTokenIsRejectedAndRemoved()
    {
        $credential = RememberTokenCredential::generate();
        $this->storage->save(new RememberToken(
            $credential->selector,
            $credential->getValidatorHash(),
            '42',
            new DateTimeImmutable('-1 second')
        ));

        try {
            $this->manager->validate($credential->toString());
            $this->fail('Expired token was accepted.');
        } catch (InvalidRememberTokenException $exception) {
            $this->assertStringContainsString('expired', $exception->getMessage());
        }

        $this->assertNull($this->storage->findBySelector($credential->selector));
    }

    public function testThatWrongValidatorRevokesAllIdentityTokens()
    {
        $stolen = $this->manager->issue('42');
        $otherDevice = $this->manager->issue('42');
        $otherUser = $this->manager->issue('7');
        $forged = new RememberTokenCredential($stolen->selector, RememberTokenCredential::generate()->validator);

        try {
            $this->manager->validate($forged->toString());
            $this->fail('Forged token was accepted.');
        } catch (InvalidRememberTokenException $exception) {
            $this->assertStringContainsString('mismatch', $exception->getMessage());
        }

        $this->assertNull($this->storage->findBySelector($stolen->selector));
        $this->assertNull($this->storage->findBySelector($otherDevice->selector));
        $this->assertNotNull($this->storage->findBySelector($otherUser->selector));
    }
}
