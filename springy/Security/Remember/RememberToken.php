<?php

/**
 * "Remember me" token as kept by the server side storage.
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 */

namespace Springy\Security\Remember;

use DateTimeImmutable;

final readonly class RememberToken
{
    public function __construct(
        public string $selector,
        public string $validatorHash,
        public string $identityId,
        public DateTimeImmutable $expiresAt,
    ) {
    }

    /**
     * Rebuilds a token from the array created by toArray().
     *
     * @throws RememberTokenStorageException when the data is incomplete.
     */
    public static function fromArray(array $data): self
    {
        foreach (['selector', 'validator_hash', 'identity_id', 'expires_at'] as $field) {
            if (!isset($data[$field])) {
                throw new RememberTokenStorageException('Stored remember token without "' . $field . '" field.');
            }
        }

        return new self(
            selector: (string) $data['selector'],
            validatorHash: (string) $data['validator_hash'],
            identityId: (string) $data['identity_id'],
            expiresAt: (new DateTimeImmutable())->setTimestamp((int) $data['expires_at']),
        );
    }

    /**
     * Returns the token as a scalar array ready to be serialized by storages.
     *
     * @return array{selector: string, validator_hash: string, identity_id: string, expires_at: int}
     */
    public function toArray(): array
    {
        return [
            'selector' => $this->selector,
            'validator_hash' => $this->validatorHash,
            'identity_id' => $this->identityId,
            'expires_at' => $this->expiresAt->getTimestamp(),
        ];
    }

    public function isExpired(DateTimeImmutable $now = new DateTimeImmutable()): bool
    {
        return $this->expiresAt <= $now;
    }

    /**
     * Checks the validator in constant time to avoid timing attacks.
     */
    public function hasValidator(string $validator): bool
    {
        return hash_equals($this->validatorHash, RememberTokenCredential::hashValidator($validator));
    }

    public function getSecondsToExpire(DateTimeImmutable $now = new DateTimeImmutable()): int
    {
        return max(0, $this->expiresAt->getTimestamp() - $now->getTimestamp());
    }
}
