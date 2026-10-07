<?php

/**
 * "Remember me" token storage driver for Redis and Valkey.
 *
 * Requires the phpredis extension. Each token is kept in its own key with
 * a TTL matching its expiration, and a set per identity indexes its tokens
 * so they can be revoked at once.
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 */

namespace Springy\Security\Remember;

use JsonException;
use Redis;
use RedisException;
use Throwable;

final class RedisRememberTokenStorage implements RememberTokenStorageInterface
{
    public const DEFAULT_PREFIX = 'springy:remember:';

    // KEYS[1] is the identity index and ARGV[1] the token key prefix.
    private const DELETE_ALL_SCRIPT = <<<'LUA'
        for _, selector in ipairs(redis.call('SMEMBERS', KEYS[1])) do
            redis.call('DEL', ARGV[1] .. selector)
        end
        return redis.call('DEL', KEYS[1])
        LUA;

    public function __construct(
        private readonly Redis $redis,
        private readonly string $prefix = self::DEFAULT_PREFIX,
    ) {
    }

    public function save(RememberToken $token): void
    {
        $ttl = $token->getSecondsToExpire();

        if ($ttl === 0) {
            return;
        }

        $tokenKey = $this->getTokenKey($token->selector);
        $identityKey = $this->getIdentityKey($token->identityId);

        try {
            $payload = json_encode($token->toArray(), JSON_THROW_ON_ERROR);

            // The newest token always has the longest TTL, so the index lives as long as any token.
            $result = $this->redis->multi()
                ->setEx($tokenKey, $ttl, $payload)
                ->sAdd($identityKey, $token->selector)
                ->expire($identityKey, $ttl)
                ->exec();
        } catch (JsonException | RedisException $exception) {
            throw $this->createFailure('save the remember token', $exception);
        }

        if (!is_array($result) || in_array(false, $result, true)) {
            throw new RememberTokenStorageException('Redis refused to save the remember token.');
        }
    }

    public function findBySelector(string $selector): ?RememberToken
    {
        try {
            $payload = $this->redis->get($this->getTokenKey($selector));
        } catch (RedisException $exception) {
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
            $result = $this->redis->multi()
                ->del($this->getTokenKey($selector))
                ->sRem($this->getIdentityKey($token->identityId), $selector)
                ->exec();
        } catch (RedisException $exception) {
            throw $this->createFailure('delete the remember token', $exception);
        }

        // DEL returns the number of removed keys, so only one concurrent call gets 1.
        return is_array($result) && ($result[0] ?? 0) > 0;
    }

    public function deleteAllByIdentity(string $identityId): void
    {
        try {
            // A script runs atomically, so no token can be added to the index between reading and deleting it.
            $result = $this->redis->eval(
                self::DELETE_ALL_SCRIPT,
                [$this->getIdentityKey($identityId), $this->getTokenKey('')],
                1
            );
        } catch (RedisException $exception) {
            throw $this->createFailure('delete the identity tokens', $exception);
        }

        if ($result === false) {
            throw new RememberTokenStorageException(
                'Redis refused to delete the identity tokens. ' . $this->redis->getLastError()
            );
        }
    }

    private function createFailure(string $action, Throwable $previous): RememberTokenStorageException
    {
        return new RememberTokenStorageException('Could not ' . $action . ' on Redis.', previous: $previous);
    }

    private function getTokenKey(string $selector): string
    {
        return $this->prefix . 'token:' . $selector;
    }

    private function getIdentityKey(string $identityId): string
    {
        return $this->prefix . 'identity:' . $identityId;
    }
}
