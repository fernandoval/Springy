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

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Springy\Security\Authentication;
use Springy\Security\AuthDriverInterface;
use Springy\Security\IdentityInterface;
use Springy\Security\Remember\RememberTokenManager;
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

        return new Authentication($this->driver, $this->manager);
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

    public function testThatLogoutFromAllDevicesRevokesEveryUserToken()
    {
        $this->manager->issue('42');
        $this->manager->issue('42');
        $otherUser = $this->manager->issue('7');
        $auth = new Authentication($this->driver, $this->manager);
        $auth->loginWithId(42);

        $auth->logoutFromAllDevices();

        $this->assertFalse($auth->check());
        $this->assertCount(1, $this->storage->tokens);
        $this->assertNotNull($this->storage->findBySelector($otherUser->selector));
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
}
