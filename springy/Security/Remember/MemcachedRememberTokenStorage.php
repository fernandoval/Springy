<?php

/**
 * "Remember me" token storage driver for MemcacheD.
 *
 * Requires the memcached extension. Memcached can not list keys, so every
 * identity has a random "generation" stamped into its tokens. Revoking all
 * tokens of an identity just drops its generation, which invalidates every
 * token stamped with it. If Memcached evicts the generation the tokens are
 * invalidated as well, so it always fails closed.
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 */

namespace Springy\Security\Remember;

use Memcached;

final class MemcachedRememberTokenStorage implements RememberTokenStorageInterface
{
    public const DEFAULT_PREFIX = 'springy.remember.';

    private const GENERATION_BYTES = 16;

    public function __construct(
        private readonly Memcached $memcached,
        private readonly string $prefix = self::DEFAULT_PREFIX,
    ) {
    }

    public function save(RememberToken $token): void
    {
        if ($token->isExpired()) {
            return;
        }

        // Absolute timestamps avoid Memcached reading TTLs above 30 days as dates in 1970.
        $expiration = $token->expiresAt->getTimestamp();
        $entry = [
            'token' => $token->toArray(),
            'generation' => $this->obtainGeneration($token->identityId, $expiration),
        ];

        if (!$this->memcached->set($this->getTokenKey($token->selector), $entry, $expiration)) {
            throw $this->createFailure('Could not save the remember token on Memcached.');
        }
    }

    public function findBySelector(string $selector): ?RememberToken
    {
        $entry = $this->fetch($this->getTokenKey($selector));

        if (!is_array($entry) || !isset($entry['token'], $entry['generation'])) {
            return null;
        }

        $token = RememberToken::fromArray($entry['token']);

        if ($this->fetch($this->getGenerationKey($token->identityId)) !== $entry['generation']) {
            $this->remove($this->getTokenKey($selector));

            return null;
        }

        return $token;
    }

    public function delete(string $selector): bool
    {
        // A token whose generation was dropped is already revoked, so removing it does not count.
        if ($this->findBySelector($selector) === null) {
            return false;
        }

        return $this->remove($this->getTokenKey($selector));
    }

    public function deleteAllByIdentity(string $identityId): void
    {
        $this->remove($this->getGenerationKey($identityId));
    }

    /**
     * Returns the current identity generation, creating it when missing.
     */
    private function obtainGeneration(string $identityId, int $expiration): string
    {
        $key = $this->getGenerationKey($identityId);
        $this->memcached->add($key, bin2hex(random_bytes(self::GENERATION_BYTES)), $expiration);
        $generation = $this->fetch($key);

        if (!is_string($generation)) {
            throw $this->createFailure('Could not create the identity generation on Memcached.');
        }

        // Keeps the generation alive as long as its newest token.
        $this->memcached->touch($key, $expiration);

        return $generation;
    }

    private function fetch(string $key): mixed
    {
        $value = $this->memcached->get($key);

        if ($value === false && $this->memcached->getResultCode() !== Memcached::RES_NOTFOUND) {
            throw $this->createFailure('Could not read from Memcached.');
        }

        return $value;
    }

    /**
     * Removes the key and returns whether it existed.
     */
    private function remove(string $key): bool
    {
        if ($this->memcached->delete($key)) {
            return true;
        }

        if ($this->memcached->getResultCode() === Memcached::RES_NOTFOUND) {
            return false;
        }

        throw $this->createFailure('Could not delete from Memcached.');
    }

    private function createFailure(string $message): RememberTokenStorageException
    {
        return new RememberTokenStorageException(
            $message . ' ' . $this->memcached->getResultMessage(),
            $this->memcached->getResultCode()
        );
    }

    private function getTokenKey(string $selector): string
    {
        return $this->prefix . 'token.' . $selector;
    }

    /**
     * Memcached keys can not hold spaces or control chars, so the identity id is hashed.
     */
    private function getGenerationKey(string $identityId): string
    {
        return $this->prefix . 'generation.' . hash('sha256', $identityId);
    }
}
