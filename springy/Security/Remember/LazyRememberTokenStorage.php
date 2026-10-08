<?php

/**
 * "Remember me" token storage that creates the real driver on first use.
 *
 * Wraps the factory of another storage driver, so the connection to its
 * backend (Redis, Memcached, database...) is opened only when a token is
 * really read, saved or revoked. Requests that never touch the "remember me"
 * feature, like the ones with an active session, do not pay for it and do
 * not depend on the backend being available.
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 */

namespace Springy\Security\Remember;

use Closure;
use Throwable;

final class LazyRememberTokenStorage implements RememberTokenStorageInterface
{
    private ?RememberTokenStorageInterface $storage = null;

    /**
     * @param Closure(): RememberTokenStorageInterface $factory
     */
    public function __construct(
        private readonly Closure $factory,
    ) {
    }

    public function save(RememberToken $token): void
    {
        $this->getStorage()->save($token);
    }

    public function findBySelector(string $selector): ?RememberToken
    {
        return $this->getStorage()->findBySelector($selector);
    }

    public function delete(string $selector): bool
    {
        return $this->getStorage()->delete($selector);
    }

    public function deleteAllByIdentity(string $identityId): void
    {
        $this->getStorage()->deleteAllByIdentity($identityId);
    }

    /**
     * Returns the real storage driver, creating it on the first call.
     *
     * @throws RememberTokenStorageException when the factory fails.
     */
    public function getStorage(): RememberTokenStorageInterface
    {
        if ($this->storage !== null) {
            return $this->storage;
        }

        try {
            $storage = ($this->factory)();
        } catch (RememberTokenStorageException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new RememberTokenStorageException(
                'Could not create the remember token storage.',
                previous: $exception
            );
        }

        if (!$storage instanceof RememberTokenStorageInterface) {
            throw new RememberTokenStorageException(
                'The remember token storage factory must return a ' . RememberTokenStorageInterface::class . '.'
            );
        }

        return $this->storage = $storage;
    }
}
