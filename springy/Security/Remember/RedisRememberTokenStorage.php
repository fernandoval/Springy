<?php

/**
 * "Remember me" token storage driver for Redis and Valkey.
 *
 * Requires the phpredis extension. The tokens of an identity are kept as
 * fields of one hash, so revoking them all is a single DEL. A pointer key
 * per selector, with a TTL matching the token expiration, maps the selector
 * to its identity.
 *
 * Every command and transaction touches only one key. So the driver works
 * on Redis Cluster, Valkey Cluster and AWS ElastiCache Serverless, which
 * reject multi-key operations spanning different hash slots (CROSSSLOT).
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 */

namespace Springy\Security\Remember;

use JsonException;
use Redis;
use RedisCluster;
use RedisClusterException;
use RedisException;
use Throwable;

final class RedisRememberTokenStorage implements RememberTokenStorageInterface
{
    public const DEFAULT_PREFIX = 'springy:remember:';

    public function __construct(
        private readonly Redis|RedisCluster $redis,
        private readonly string $prefix = self::DEFAULT_PREFIX,
    ) {
    }

    public function save(RememberToken $token): void
    {
        $ttl = $token->getSecondsToExpire();

        if ($ttl === 0) {
            return;
        }

        $tokensKey = $this->getTokensKey($token->identityId);

        try {
            $payload = json_encode($token->toArray(), JSON_THROW_ON_ERROR);

            // The newest token always has the longest TTL, so the hash lives as long as any token.
            $result = $this->redis->multi()
                ->hSet($tokensKey, $token->selector, $payload)
                ->expire($tokensKey, $ttl)
                ->exec();

            // Written after the hash field: a revocation in between deletes the field and the pointer finds nothing.
            $pointer = $this->redis->setEx($this->getSelectorKey($token->selector), $ttl, $token->identityId);
        } catch (JsonException | RedisException | RedisClusterException $exception) {
            throw $this->createFailure('save the remember token', $exception);
        }

        if (!is_array($result) || in_array(false, $result, true) || $pointer === false) {
            throw new RememberTokenStorageException('Redis refused to save the remember token.');
        }
    }

    public function findBySelector(string $selector): ?RememberToken
    {
        try {
            $identityId = $this->redis->get($this->getSelectorKey($selector));

            // A missing field means the identity tokens were revoked.
            $payload = is_string($identityId)
                ? $this->redis->hGet($this->getTokensKey($identityId), $selector)
                : false;
        } catch (RedisException | RedisClusterException $exception) {
            throw $this->createFailure('read the remember token', $exception);
        }

        if (!is_string($payload)) {
            return null;
        }

        try {
            return RememberToken::fromArray(json_decode($payload, true, flags: JSON_THROW_ON_ERROR));
        } catch (JsonException $exception) {
            throw $this->createFailure('decode the remember token', $exception);
        }
    }

    public function delete(string $selector): bool
    {
        $token = $this->findBySelector($selector);

        if ($token === null) {
            return false;
        }

        try {
            // HDEL returns the number of removed fields, so only one concurrent call gets 1.
            $removed = $this->redis->hDel($this->getTokensKey($token->identityId), $selector);
        } catch (RedisException | RedisClusterException $exception) {
            throw $this->createFailure('delete the remember token', $exception);
        }

        $this->deleteSelectorKeys([$selector]);

        return is_int($removed) && $removed > 0;
    }

    public function deleteAllByIdentity(string $identityId): void
    {
        $tokensKey = $this->getTokensKey($identityId);

        try {
            // Revoking is the DEL alone. HKEYS runs in the same transaction only to find the pointers to clean up.
            $result = $this->redis->multi()
                ->hKeys($tokensKey)
                ->del($tokensKey)
                ->exec();
        } catch (RedisException | RedisClusterException $exception) {
            throw $this->createFailure('delete the identity tokens', $exception);
        }

        if (!is_array($result) || !is_array($result[0] ?? null) || !is_int($result[1] ?? null)) {
            throw new RememberTokenStorageException(
                'Redis refused to delete the identity tokens. ' . $this->redis->getLastError()
            );
        }

        $this->deleteSelectorKeys($result[0]);
    }

    private function createFailure(string $action, Throwable $previous): RememberTokenStorageException
    {
        return new RememberTokenStorageException('Could not ' . $action . ' on Redis.', previous: $previous);
    }

    /**
     * Removes the pointers of tokens already deleted from the identity hash.
     *
     * It is only a cleanup: a pointer without its hash field finds no token,
     * and it expires with the token anyway. So failures are ignored. Each key
     * is deleted by its own command because the pointers live in different
     * hash slots.
     *
     * @param string[] $selectors
     */
    private function deleteSelectorKeys(array $selectors): void
    {
        try {
            foreach ($selectors as $selector) {
                $this->redis->del($this->getSelectorKey($selector));
            }
        } catch (RedisException | RedisClusterException) {
            // The revocation is already done.
        }
    }

    private function getSelectorKey(string $selector): string
    {
        return $this->prefix . 'selector:' . $selector;
    }

    private function getTokensKey(string $identityId): string
    {
        return $this->prefix . 'tokens:' . $identityId;
    }
}
