<?php

/**
 * Interface to standardize "remember me" token storage drivers.
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 */

namespace Springy\Security\Remember;

interface RememberTokenStorageInterface
{
    /**
     * Persists the token until its expiration time.
     *
     * @throws RememberTokenStorageException when the storage fails.
     */
    public function save(RememberToken $token): void;

    /**
     * Returns the token identified by the selector or null when it does not exist.
     *
     * Implementations must not return revoked tokens.
     *
     * @throws RememberTokenStorageException when the storage fails.
     */
    public function findBySelector(string $selector): ?RememberToken;

    /**
     * Removes the token identified by the selector, if it exists.
     *
     * @throws RememberTokenStorageException when the storage fails.
     */
    public function delete(string $selector): void;

    /**
     * Removes every token that belongs to the identity.
     *
     * @throws RememberTokenStorageException when the storage fails.
     */
    public function deleteAllByIdentity(string $identityId): void;
}
