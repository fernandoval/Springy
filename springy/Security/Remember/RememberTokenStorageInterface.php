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
     * Returns true only when this call removed a token that was still valid
     * (not revoked). When concurrent calls remove the same token, at most one
     * of them returns true. The token rotation relies on this to consume a
     * token only once.
     *
     * @throws RememberTokenStorageException when the storage fails.
     */
    public function delete(string $selector): bool;

    /**
     * Removes every token that belongs to the identity.
     *
     * Must be atomic regarding save(): every token saved before this call
     * begins must be revoked when it returns.
     *
     * @throws RememberTokenStorageException when the storage fails.
     */
    public function deleteAllByIdentity(string $identityId): void;
}
