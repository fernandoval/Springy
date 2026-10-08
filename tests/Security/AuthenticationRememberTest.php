<?php

/**
 * Test case for the "remember me" feature of Security\Authentication class.
 *
 * Each test runs in its own process because Session and Cookie keep static state.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

require_once __DIR__ . '/Remember/InMemoryRememberTokenStorage.php';
require_once __DIR__ . '/Remember/InterleavedRememberTokenStorage.php';
require_once __DIR__ . '/RecordingAuthentication.php';

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Springy\Security\Authentication;
use Springy\Security\AuthDriverInterface;
use Springy\Security\IdentityInterface;
use Springy\Security\Remember\LazyRememberTokenStorage;
use Springy\Security\Remember\RememberToken;
use Springy\Security\Remember\RememberTokenCredential;
use Springy\Security\Remember\RememberTokenManager;
use Springy\Security\Remember\RememberTokenStorageException;
use Springy\Session;

#[RunTestsInSeparateProcesses]
class AuthenticationRememberTest extends TestCase
{
    private const KEY = '_identity';

    private InMemoryRememberTokenStorage $storage;
    private RememberTokenManager $manager;
    private AuthDriverInterface $driver;

    protected function setUp(): void
    {
        $_COOKIE = [];
        RecordingAuthentication::reset();
        $this->storage = new InMemoryRememberTokenStorage();
        $this->manager = new RememberTokenManager($this->storage, 3600);
        $this->driver = $this->createDriver([42 => 'johndoe', 7 => 'janedoe']);
    }

    /**
     * Creates an authentication driver backed by the given users (id => login).
     */
    private function createDriver(array $users): AuthDriverInterface
    {
        $identity = new class ($users) implements IdentityInterface {
            private array $data = [];

            public function __construct(private array $users)
            {
            }

            public function fillFromSession(array $data): void
            {
                $this->data = $data;
            }

            public function getId(): mixed
            {
                return $this->data['id'] ?? null;
            }

            public function getIdField(): string
            {
                return 'id';
            }

            public function getSessionKey(): string
            {
                return '_identity';
            }

            public function getSessionData(): array
            {
                return $this->data;
            }

            public function getCredentials(): array
            {
                return ['login' => 'login', 'password' => 'password'];
            }

            public function hasPermissionFor(string $aclObjectName): bool
            {
                return false;
            }

            public function isLoaded(): bool
            {
                return $this->data !== [];
            }

            public function loadByCredentials(array $data): void
            {
                $id = (int) ($data['id'] ?? 0);
                $this->data = isset($this->users[$id]) ? ['id' => $id, 'login' => $this->users[$id]] : [];
            }
        };

        return new class ($identity) implements AuthDriverInterface {
            public function __construct(private IdentityInterface $identity)
            {
            }

            public function getIdentitySessionKey(): string
            {
                return $this->identity->getSessionKey();
            }

            public function isValid(string $login, string $password): bool
            {
                return false;
            }

            public function setDefaultIdentity(IdentityInterface $identity): void
            {
                $this->identity = $identity;
            }

            public function getDefaultIdentity(): IdentityInterface
            {
                return $this->identity;
            }

            public function getLastValidIdentity(): ?IdentityInterface
            {
                return null;
            }

            public function getIdentityById($iid): IdentityInterface
            {
                $identity = clone $this->identity;
                $identity->loadByCredentials(['id' => $iid]);

                return $identity;
            }
        };
    }

    /**
     * Simulates a new request from a browser without session that holds the given cookie.
     */
    private function startRequestWithCookie(string $cookieValue): Authentication
    {
        Session::unregister(self::KEY);
        $_COOKIE[self::KEY] = $cookieValue;

        return new RecordingAuthentication($this->driver, $this->manager);
    }

    public function testThatLoginWithRememberIssuesAToken()
    {
        $auth = new Authentication($this->driver, $this->manager);
        $auth->loginWithId(42, true);

        $this->assertTrue($auth->check());
        $this->assertCount(1, $this->storage->tokens);
        $this->assertSame('42', current($this->storage->tokens)->identityId);
    }

    public function testThatLoginWithoutRememberDoesNotIssueAToken()
    {
        $auth = new Authentication($this->driver, $this->manager);
        $auth->loginWithId(42);

        $this->assertTrue($auth->check());
        $this->assertSame([], $this->storage->tokens);
    }

    public function testThatValidCookieRestoresTheUserAndRotatesTheToken()
    {
        $credential = $this->manager->issue('42');

        $auth = $this->startRequestWithCookie($credential->toString());

        $this->assertTrue($auth->check());
        $this->assertSame(42, $auth->user()->getId());
        $this->assertNull($this->storage->findBySelector($credential->selector));
        $this->assertCount(1, $this->storage->tokens);
        $this->assertSame('42', current($this->storage->tokens)->identityId);
    }

    public function testThatRevokedTokensForceANewLogon()
    {
        $cookieValue = $this->manager->issue('42')->toString();

        $this->manager->revokeAllFor('42');
        $auth = $this->startRequestWithCookie($cookieValue);

        $this->assertFalse($auth->check());
        $this->assertArrayNotHasKey(self::KEY, $_COOKIE);
    }

    public function testThatLegacyCookieWithUserIdNoLongerLogsIn()
    {
        $auth = $this->startRequestWithCookie('42');

        $this->assertFalse($auth->check());
        $this->assertArrayNotHasKey(self::KEY, $_COOKIE);
    }

    public function testThatCookieIsIgnoredWithoutRememberTokenManager()
    {
        $cookieValue = $this->manager->issue('42')->toString();
        Session::unregister(self::KEY);
        $_COOKIE[self::KEY] = $cookieValue;

        $auth = new Authentication($this->driver);
        $auth->loginWithId(7, true);

        $this->assertSame(7, $auth->user()->getId());
        $this->assertCount(1, $this->storage->tokens);
    }

    public function testThatTokenOfRemovedUserIsDiscarded()
    {
        $cookieValue = $this->manager->issue('99')->toString();

        $auth = $this->startRequestWithCookie($cookieValue);

        $this->assertFalse($auth->check());
        $this->assertSame([], $this->storage->tokens);
        $this->assertArrayNotHasKey(self::KEY, $_COOKIE);
    }

    public function testThatLogoutRevokesTheCurrentToken()
    {
        $current = $this->manager->issue('42');
        $otherDevice = $this->manager->issue('42');
        $auth = new Authentication($this->driver, $this->manager);
        $auth->loginWithId(42);
        $_COOKIE[self::KEY] = $current->toString();

        $auth->logout();

        $this->assertFalse($auth->check());
        $this->assertNull($this->storage->findBySelector($current->selector));
        $this->assertNotNull($this->storage->findBySelector($otherDevice->selector));
    }

    public function testThatLogoutAndRevokeRememberTokensRevokesEveryUserToken()
    {
        $this->manager->issue('42');
        $this->manager->issue('42');
        $otherUser = $this->manager->issue('7');
        $auth = new Authentication($this->driver, $this->manager);
        $auth->loginWithId(42);

        $auth->logoutAndRevokeRememberTokens();

        $this->assertFalse($auth->check());
        $this->assertCount(1, $this->storage->tokens);
        $this->assertNotNull($this->storage->findBySelector($otherUser->selector));
    }

    public function testThatStorageOutageKeepsTheRequestAnonymousAndTheCookie()
    {
        $cookieValue = $this->manager->issue('42')->toString();
        $this->manager = $this->createUnavailableManager();

        $auth = $this->startRequestWithCookie($cookieValue);

        $this->assertFalse($auth->check());
        $this->assertSame($cookieValue, $_COOKIE[self::KEY] ?? null);
        $this->assertCount(1, $this->storage->tokens);
    }

    public function testThatLogoutClearsTheCookieDuringStorageOutage()
    {
        $auth = new Authentication($this->driver, $this->createUnavailableManager());
        $auth->loginWithId(42);
        $_COOKIE[self::KEY] = $this->manager->issue('42')->toString();

        $auth->logout();

        $this->assertFalse($auth->check());
        $this->assertNull(Session::get(self::KEY));
        $this->assertArrayNotHasKey(self::KEY, $_COOKIE);
    }

    public function testThatLogoutAndRevokeRememberTokensLogsOutBeforeReportingStorageOutage()
    {
        $auth = new Authentication($this->driver, $this->createUnavailableManager());
        $auth->loginWithId(42);
        $_COOKIE[self::KEY] = $this->manager->issue('42')->toString();

        try {
            $auth->logoutAndRevokeRememberTokens();
            $this->fail('RememberTokenStorageException was not thrown.');
        } catch (RememberTokenStorageException) {
        }

        $this->assertFalse($auth->check());
        $this->assertNull(Session::get(self::KEY));
        $this->assertArrayNotHasKey(self::KEY, $_COOKIE);
    }

    public function testThatLoginWithRememberDuringStorageOutageDoesNotAuthenticate()
    {
        $auth = new Authentication($this->driver, $this->createUnavailableManager());

        try {
            $auth->loginWithId(42, true);
            $this->fail('RememberTokenStorageException was not thrown.');
        } catch (RememberTokenStorageException) {
        }

        $this->assertFalse($auth->check());
        $this->assertNull(Session::get(self::KEY));
        $this->assertArrayNotHasKey(self::KEY, $_COOKIE);
    }

    public function testThatDriverIsRequired()
    {
        $driver = (new ReflectionMethod(Authentication::class, '__construct'))->getParameters()[0];

        $this->assertFalse($driver->allowsNull());
        $this->assertFalse($driver->isOptional());
    }

    public function testThatRevokeAllDuringRestorationIsNotBypassed()
    {
        $storage = new InterleavedRememberTokenStorage($this->storage);
        $this->manager = new RememberTokenManager($storage, 3600);
        $cookieValue = $this->manager->issue('42')->toString();
        $storage->before('delete', fn () => $this->manager->revokeAllFor('42'));

        $auth = $this->startRequestWithCookie($cookieValue);

        $this->assertFalse($auth->check());
        $this->assertSame([], $this->storage->tokens);
        $this->assertArrayNotHasKey(self::KEY, $_COOKIE);
    }

    public function testThatRestorationSavesTheSessionAndSendsTheRotatedCookie()
    {
        $credential = $this->manager->issue('42');

        $auth = $this->startRequestWithCookie($credential->toString());

        $newSelector = array_key_first($this->storage->tokens);
        $this->assertTrue($auth->check());
        $this->assertSame(['id' => 42, 'login' => 'johndoe'], Session::get(self::KEY));
        $this->assertCount(1, RecordingAuthentication::$sentCookies);
        $this->assertSame(3600, RecordingAuthentication::$sentCookies[0]['lifetime']);
        $this->assertSame(
            $newSelector,
            RememberTokenCredential::fromString(RecordingAuthentication::$sentCookies[0]['value'])->selector
        );
        $this->assertNotSame($credential->selector, $newSelector);
        $this->assertSame(0, RecordingAuthentication::$forgottenCookies);
    }

    public function testThatRotatedCookieCanNotBeReplayed()
    {
        $oldCookieValue = $this->manager->issue('42')->toString();
        $this->startRequestWithCookie($oldCookieValue);
        RecordingAuthentication::reset();

        $auth = $this->startRequestWithCookie($oldCookieValue);

        $this->assertFalse($auth->check());
        $this->assertNull(Session::get(self::KEY));
        $this->assertSame([], RecordingAuthentication::$sentCookies);
        $this->assertSame(1, RecordingAuthentication::$forgottenCookies);
        $this->assertArrayNotHasKey(self::KEY, $_COOKIE);
        $this->assertCount(1, $this->storage->tokens);
    }

    public function testThatTamperedValidatorRevokesEveryTokenOfTheIdentity()
    {
        $credential = $this->manager->issue('42');
        $this->manager->issue('42');
        $otherUser = $this->manager->issue('7');
        $tampered = new RememberTokenCredential($credential->selector, str_repeat('0', 64));

        $auth = $this->startRequestWithCookie($tampered->toString());

        $this->assertFalse($auth->check());
        $this->assertNull(Session::get(self::KEY));
        $this->assertSame(1, RecordingAuthentication::$forgottenCookies);
        $this->assertArrayNotHasKey(self::KEY, $_COOKIE);
        $this->assertSame([$otherUser->selector], array_keys($this->storage->tokens));
    }

    public function testThatExpiredTokenIsDiscarded()
    {
        $credential = RememberTokenCredential::generate();
        $this->storage->save(new RememberToken(
            selector: $credential->selector,
            validatorHash: $credential->getValidatorHash(),
            identityId: '42',
            expiresAt: new DateTimeImmutable('-1 second'),
        ));

        $auth = $this->startRequestWithCookie($credential->toString());

        $this->assertFalse($auth->check());
        $this->assertSame([], $this->storage->tokens);
        $this->assertSame(1, RecordingAuthentication::$forgottenCookies);
        $this->assertArrayNotHasKey(self::KEY, $_COOKIE);
    }

    public function testThatActiveSessionDoesNotConsumeTheCookie()
    {
        $credential = $this->manager->issue('42');
        Session::set(self::KEY, ['id' => 7, 'login' => 'janedoe']);
        $_COOKIE[self::KEY] = $credential->toString();

        $auth = new RecordingAuthentication($this->driver, $this->manager);

        $this->assertSame(7, $auth->user()->getId());
        $this->assertNotNull($this->storage->findBySelector($credential->selector));
        $this->assertCount(1, $this->storage->tokens);
        $this->assertSame([], RecordingAuthentication::$sentCookies);
        $this->assertSame(0, RecordingAuthentication::$forgottenCookies);
        $this->assertSame($credential->toString(), $_COOKIE[self::KEY]);
    }

    public function testThatStorageFailureDuringRotationKeepsTheCookieForALaterRequest()
    {
        $storage = new InterleavedRememberTokenStorage($this->storage);
        $this->manager = new RememberTokenManager($storage, 3600);
        $credential = $this->manager->issue('42');
        $storage->before('delete', fn () => throw new RememberTokenStorageException('Connection lost.'));

        $auth = $this->startRequestWithCookie($credential->toString());

        $this->assertFalse($auth->check());
        $this->assertNull(Session::get(self::KEY));
        $this->assertSame([], RecordingAuthentication::$sentCookies);
        $this->assertSame(0, RecordingAuthentication::$forgottenCookies);
        $this->assertSame($credential->toString(), $_COOKIE[self::KEY]);
        $this->assertNotNull($this->storage->findBySelector($credential->selector));

        $auth = $this->startRequestWithCookie($credential->toString());

        $this->assertTrue($auth->check());
        $this->assertSame(42, $auth->user()->getId());
        $this->assertNull($this->storage->findBySelector($credential->selector));
    }

    public function testThatRemovedIdentityDiscardsTheCookieEvenIfRevocationFails()
    {
        $storage = new InterleavedRememberTokenStorage($this->storage);
        $this->manager = new RememberTokenManager($storage, 3600);
        $cookieValue = $this->manager->issue('99')->toString();
        // The first delete consumes the old token during rotation; the second one revokes the new token.
        $storage->before('delete', fn () => $storage->before(
            'delete',
            fn () => throw new RememberTokenStorageException('Connection lost.')
        ));

        $auth = $this->startRequestWithCookie($cookieValue);

        $this->assertFalse($auth->check());
        $this->assertNull(Session::get(self::KEY));
        $this->assertSame([], RecordingAuthentication::$sentCookies);
        $this->assertSame(1, RecordingAuthentication::$forgottenCookies);
        $this->assertArrayNotHasKey(self::KEY, $_COOKIE);
        $this->assertSame([$storage->savedSelectors[1]], array_keys($this->storage->tokens));
    }

    /**
     * Creates a token manager whose storage backend is unreachable.
     */
    private function createUnavailableManager(): RememberTokenManager
    {
        return new RememberTokenManager(
            new LazyRememberTokenStorage(fn () => throw new RuntimeException('Connection refused.')),
            3600
        );
    }
}
