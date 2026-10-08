<?php

/**
 * Authentication subclass that records the "remember me" cookies sent to the client.
 *
 * setcookie() headers can not be read back in CLI, so the tests use this
 * record to check which value and lifetime were sent.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

use Springy\Security\Authentication;
use Springy\Security\Remember\RememberTokenCredential;

final class RecordingAuthentication extends Authentication
{
    /** @var array<int, array{value: string, lifetime: int}> cookies sent, in order. */
    public static array $sentCookies = [];

    /** Number of times the cookie removal was sent. */
    public static int $forgottenCookies = 0;

    public static function reset(): void
    {
        self::$sentCookies = [];
        self::$forgottenCookies = 0;
    }

    protected function saveRememberCookie(RememberTokenCredential $credential, int $lifetime): void
    {
        self::$sentCookies[] = ['value' => $credential->toString(), 'lifetime' => $lifetime];

        parent::saveRememberCookie($credential, $lifetime);
    }

    protected function forgetRememberCookie(): void
    {
        ++self::$forgottenCookies;

        parent::forgetRememberCookie();
    }
}
