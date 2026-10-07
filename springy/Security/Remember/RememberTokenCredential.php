<?php

/**
 * "Remember me" credential as kept by the client in a cookie.
 *
 * The credential is split in a public selector, used to find the token in the
 * storage, and a secret validator, of which only the hash is stored. A leaked
 * storage therefore does not allow anyone to forge a valid cookie.
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 */

namespace Springy\Security\Remember;

final readonly class RememberTokenCredential
{
    public const SEPARATOR = ':';
    public const SELECTOR_BYTES = 12;
    public const VALIDATOR_BYTES = 32;

    private const HASH_ALGORITHM = 'sha256';

    public function __construct(
        public string $selector,
        public string $validator,
    ) {
    }

    public static function generate(): self
    {
        return new self(
            selector: bin2hex(random_bytes(self::SELECTOR_BYTES)),
            validator: bin2hex(random_bytes(self::VALIDATOR_BYTES)),
        );
    }

    /**
     * Parses the cookie value.
     *
     * @throws InvalidRememberTokenException when the value is malformed.
     */
    public static function fromString(string $value): self
    {
        $pattern = sprintf(
            '/^([0-9a-f]{%d})%s([0-9a-f]{%d})$/D',
            self::SELECTOR_BYTES * 2,
            preg_quote(self::SEPARATOR, '/'),
            self::VALIDATOR_BYTES * 2
        );

        if (preg_match($pattern, $value, $matches) !== 1) {
            throw InvalidRememberTokenException::malformed();
        }

        return new self(selector: $matches[1], validator: $matches[2]);
    }

    public static function hashValidator(string $validator): string
    {
        return hash(self::HASH_ALGORITHM, $validator);
    }

    public function getValidatorHash(): string
    {
        return self::hashValidator($this->validator);
    }

    public function toString(): string
    {
        return $this->selector . self::SEPARATOR . $this->validator;
    }
}
