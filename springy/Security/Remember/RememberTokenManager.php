<?php

/**
 * "Remember me" token manager.
 *
 * Issues, validates and revokes the tokens kept in the "remember me" cookie,
 * delegating persistence to the chosen storage driver.
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 */

namespace Springy\Security\Remember;

use DateTimeImmutable;
use InvalidArgumentException;

final class RememberTokenManager
{
    /** 60 days */
    public const DEFAULT_LIFETIME = 5184000;

    public function __construct(
        private readonly RememberTokenStorageInterface $storage,
        private readonly int $lifetime = self::DEFAULT_LIFETIME,
    ) {
        if ($lifetime <= 0) {
            throw new InvalidArgumentException('The remember token lifetime must be greater than zero.');
        }
    }

    public function getStorage(): RememberTokenStorageInterface
    {
        return $this->storage;
    }

    /**
     * Returns the token lifetime in seconds.
     */
    public function getLifetime(): int
    {
        return $this->lifetime;
    }

    /**
     * Creates and stores a new token for the identity.
     *
     * The returned credential must be sent to the client. It is the only place
     * where the plain validator exists.
     */
    public function issue(string $identityId): RememberTokenCredential
    {
        $credential = RememberTokenCredential::generate();

        $this->storage->save(new RememberToken(
            selector: $credential->selector,
            validatorHash: $credential->getValidatorHash(),
            identityId: $identityId,
            expiresAt: new DateTimeImmutable('+' . $this->lifetime . ' seconds'),
        ));

        return $credential;
    }

    /**
     * Validates the cookie value and returns the identity id that owns it.
     *
     * @throws InvalidRememberTokenException when the cookie value can not be accepted.
     */
    public function validate(string $cookieValue): string
    {
        return $this->findValidToken($cookieValue)->identityId;
    }

    /**
     * Replaces the token kept in the cookie value by a new one.
     *
     * The new token is saved before the old one is consumed, and it is
     * discarded when the old one was already gone. So a revokeAllFor() running
     * at the same time either finds the new token and revokes it, or revokes
     * the old one first and the rotation fails. The same happens when two
     * requests rotate the same cookie: only one of them succeeds.
     *
     * @return array{0: string, 1: RememberTokenCredential} the identity id and the new credential.
     *
     * @throws InvalidRememberTokenException when the cookie value can not be accepted.
     */
    public function rotate(string $cookieValue): array
    {
        $token = $this->findValidToken($cookieValue);
        $credential = $this->issue($token->identityId);

        if (!$this->storage->delete($token->selector)) {
            $this->storage->delete($credential->selector);

            throw InvalidRememberTokenException::notFound();
        }

        return [$token->identityId, $credential];
    }

    /**
     * Invalidates the token kept in the cookie value.
     *
     * Malformed values are ignored because there is nothing stored to revoke.
     */
    public function revoke(string $cookieValue): void
    {
        try {
            $credential = RememberTokenCredential::fromString($cookieValue);
        } catch (InvalidRememberTokenException) {
            return;
        }

        $this->storage->delete($credential->selector);
    }

    /**
     * Invalidates every token of the identity, forcing a new logon on all devices.
     */
    public function revokeAllFor(string $identityId): void
    {
        $this->storage->deleteAllByIdentity($identityId);
    }

    /**
     * Finds the stored token for the cookie value and checks it.
     *
     * A known selector with a wrong validator means the cookie was probably
     * stolen and already used by someone else (tokens rotate on each use),
     * so every token of that identity is revoked.
     *
     * @throws InvalidRememberTokenException when the cookie value can not be accepted.
     */
    private function findValidToken(string $cookieValue): RememberToken
    {
        $credential = RememberTokenCredential::fromString($cookieValue);
        $token = $this->storage->findBySelector($credential->selector);

        if ($token === null) {
            throw InvalidRememberTokenException::notFound();
        }

        if ($token->isExpired()) {
            $this->storage->delete($token->selector);

            throw InvalidRememberTokenException::expired();
        }

        if (!$token->hasValidator($credential->validator)) {
            $this->storage->deleteAllByIdentity($token->identityId);

            throw InvalidRememberTokenException::validatorMismatch();
        }

        return $token;
    }
}
