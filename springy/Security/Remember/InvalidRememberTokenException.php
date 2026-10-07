<?php

/**
 * Exception thrown when a "remember me" cookie can not be accepted.
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 */

namespace Springy\Security\Remember;

use Springy\Exceptions\SpringyException;

final class InvalidRememberTokenException extends SpringyException
{
    public static function malformed(): self
    {
        return new self('Malformed remember token.');
    }

    public static function notFound(): self
    {
        return new self('Remember token not found or revoked.');
    }

    public static function expired(): self
    {
        return new self('Remember token expired.');
    }

    public static function validatorMismatch(): self
    {
        return new self('Remember token validator mismatch. All identity tokens were revoked.');
    }
}
