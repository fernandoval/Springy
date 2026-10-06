<?php

/**
 * Test case for Security\DBAuthDriver class.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   1.0.0
 */

use PHPUnit\Framework\TestCase;
use Springy\Core\Application;
use Springy\Security\AuthDriverInterface;
use Springy\Security\BasicHasher;
use Springy\Security\BCryptHasher;
use Springy\Security\DBAuthDriver;
use Springy\Security\HasherInterface;
use Springy\Security\IdentityInterface;

class DBAuthDriverTest extends TestCase
{
    private const EVENTS = ['auth.attempt', 'auth.success', 'auth.fail'];

    public DBAuthDriver $authDriver;
    public BasicHasher $hasher;
    public IdentityInterface $identity;
    /** Events fired by the driver, in order, with their arguments */
    public array $firedEvents = [];

    protected function setUp(): void
    {
        $this->hasher = new BasicHasher();
        $this->identity = $this->createIdentity([
            1 => ['id' => 1, 'login' => 'johndoe', 'password' => $this->hasher->make('secret')],
            2 => ['id' => 2, 'login' => 'janedoe', 'password' => $this->hasher->make('another')],
            3 => ['id' => 3, 'login' => 'nopass', 'password' => null],
        ]);
        $this->authDriver = new DBAuthDriver($this->hasher, $this->identity);

        $app = Application::sharedInstance();
        foreach (self::EVENTS as $event) {
            $app->on($event, function (...$args) use ($event) {
                $this->firedEvents[] = [$event, $args];
            });
        }
    }

    protected function tearDown(): void
    {
        $app = Application::sharedInstance();
        foreach (self::EVENTS as $event) {
            $app->off($event);
        }
    }

    /**
     * Creates an in-memory identity backed by the given rows.
     */
    private function createIdentity(array $rows): IdentityInterface
    {
        return new class ($rows) implements IdentityInterface {
            public array $loadedWith = [];
            private array $data = [];

            public function __construct(private array $rows)
            {
            }

            public function __get(string $name): mixed
            {
                return $this->data[$name] ?? null;
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
                return !empty($this->data);
            }

            public function loadByCredentials(array $data): void
            {
                $this->loadedWith = $data;
                $this->data = [];

                foreach ($this->rows as $row) {
                    if (array_intersect_assoc($data, $row) == $data) {
                        $this->data = $row;

                        return;
                    }
                }
            }
        };
    }

    private function firedEventNames(): array
    {
        return array_column($this->firedEvents, 0);
    }

    public function testThatDriverImplementsAuthDriverInterface()
    {
        $this->assertInstanceOf(AuthDriverInterface::class, $this->authDriver);
    }

    public function testThatConstructorSetsHasherAndDefaultIdentity()
    {
        $this->assertSame($this->hasher, $this->authDriver->getHasher());
        $this->assertSame($this->identity, $this->authDriver->getDefaultIdentity());
    }

    public function testThatHasherCanBeReplaced()
    {
        $hasher = new BCryptHasher();
        $this->authDriver->setHasher($hasher);

        $this->assertSame($hasher, $this->authDriver->getHasher());
    }

    public function testThatDefaultIdentityCanBeReplaced()
    {
        $identity = $this->createIdentity([]);
        $this->authDriver->setDefaultIdentity($identity);

        $this->assertSame($identity, $this->authDriver->getDefaultIdentity());
    }

    public function testThatIdentitySessionKeyComesFromTheIdentity()
    {
        $this->assertSame('_identity', $this->authDriver->getIdentitySessionKey());
    }

    public function testThatIdentityCanBeLoadedById()
    {
        $identity = $this->authDriver->getIdentityById(2);

        $this->assertSame($this->identity, $identity);
        $this->assertSame(['id' => 2], $this->identity->loadedWith);
        $this->assertTrue($identity->isLoaded());
        $this->assertSame(2, $identity->getId());
        $this->assertSame('janedoe', $identity->login);
    }

    public function testThatUnknownIdReturnsAnUnloadedIdentity()
    {
        $identity = $this->authDriver->getIdentityById(99);

        $this->assertFalse($identity->isLoaded());
        $this->assertNull($identity->getId());
    }

    public function testThatValidCredentialsAreAccepted()
    {
        $this->assertTrue($this->authDriver->isValid('johndoe', 'secret'));
        $this->assertSame(['login' => 'johndoe'], $this->identity->loadedWith);
    }

    public function testThatLastValidIdentityIsNullBeforeAnySuccessfulAuthentication()
    {
        $this->assertNull($this->authDriver->getLastValidIdentity());

        $this->authDriver->isValid('johndoe', 'wrong');
        $this->assertNull($this->authDriver->getLastValidIdentity());
    }

    public function testThatLastValidIdentityIsACloneOfTheAuthenticatedIdentity()
    {
        $this->authDriver->isValid('johndoe', 'secret');
        $lastValid = $this->authDriver->getLastValidIdentity();

        $this->assertNotSame($this->identity, $lastValid);
        $this->assertSame(1, $lastValid->getId());

        // Loading another identity must not change the last valid one
        $this->authDriver->getIdentityById(2);
        $this->assertSame(1, $lastValid->getId());
        $this->assertSame(1, $this->authDriver->getLastValidIdentity()->getId());
    }

    public function testThatFailedAttemptKeepsThePreviousLastValidIdentity()
    {
        $this->authDriver->isValid('johndoe', 'secret');
        $this->authDriver->isValid('janedoe', 'wrong');

        $this->assertSame(1, $this->authDriver->getLastValidIdentity()->getId());
    }

    public function testThatWrongPasswordIsRejected()
    {
        $this->assertFalse($this->authDriver->isValid('johndoe', 'wrong'));
        $this->assertFalse($this->authDriver->isValid('johndoe', ''));
        $this->assertFalse($this->authDriver->isValid('johndoe', 'another'));
    }

    public function testThatUnknownLoginIsRejected()
    {
        $this->assertFalse($this->authDriver->isValid('nobody', 'secret'));
    }

    public function testThatIdentityWithoutPasswordIsRejectedWithoutCallingTheHasher()
    {
        $hasher = $this->createMock(HasherInterface::class);
        $hasher->expects($this->never())->method('verify');
        $this->authDriver->setHasher($hasher);

        $this->assertFalse($this->authDriver->isValid('nopass', ''));
    }

    public function testThatPasswordIsVerifiedByTheHasher()
    {
        $hasher = $this->createMock(HasherInterface::class);
        $hasher->expects($this->once())
            ->method('verify')
            ->with('typed', $this->hasher->make('secret'))
            ->willReturn(true);
        $this->authDriver->setHasher($hasher);

        $this->assertTrue($this->authDriver->isValid('johndoe', 'typed'));
    }

    public function testThatSuccessfulAuthenticationFiresAttemptAndSuccessEvents()
    {
        $this->authDriver->isValid('johndoe', 'secret');

        $this->assertSame(['auth.attempt', 'auth.success'], $this->firedEventNames());
        $this->assertSame(['johndoe', 'secret'], $this->firedEvents[0][1]);
        $this->assertSame([$this->authDriver->getLastValidIdentity()], $this->firedEvents[1][1]);
    }

    public function testThatFailedAuthenticationFiresAttemptAndFailEvents()
    {
        $this->authDriver->isValid('johndoe', 'wrong');

        $this->assertSame(['auth.attempt', 'auth.fail'], $this->firedEventNames());
        $this->assertSame(['johndoe', 'wrong'], $this->firedEvents[0][1]);
        $this->assertSame(['johndoe', 'wrong'], $this->firedEvents[1][1]);
    }

    public function testThatDriverWorksWithBCryptHasher()
    {
        $hasher = new BCryptHasher();
        $identity = $this->createIdentity([
            ['id' => 10, 'login' => 'bcrypt', 'password' => $hasher->make('secret', 4)],
        ]);
        $driver = new DBAuthDriver($hasher, $identity);

        $this->assertTrue($driver->isValid('bcrypt', 'secret'));
        $this->assertFalse($driver->isValid('bcrypt', 'Secret'));
        $this->assertSame(10, $driver->getLastValidIdentity()->getId());
    }
}
