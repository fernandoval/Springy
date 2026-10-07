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

    public function delete(string $selector): void
    {
        $token = $this->findBySelector($selector);

        if ($token === null) {
            return;
        }

        try {
            $this->redis->multi()
                ->del($this->getTokenKey($selector))
                ->sRem($this->getIdentityKey($token->identityId), $selector)
                ->exec();
        } catch (RedisException $exception) {
            throw $this->createFailure('delete the remember token', $exception);
        }
    }

    public function deleteAllByIdentity(string $identityId): void
    {
        $identityKey = $this->getIdentityKey($identityId);

        try {
            $selectors = $this->redis->sMembers($identityKey);
            $keys = array_map($this->getTokenKey(...), is_array($selectors) ? $selectors : []);
            $this->redis->del([...$keys, $identityKey]);
        } catch (RedisException $exception) {
            throw $this->createFailure('delete the identity tokens', $exception);
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
