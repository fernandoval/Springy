<?php

/**
 * Identity authentication manager.
 *
 * @copyright 2014 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Allan Marques <allan.marques@ymail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 */

namespace Springy\Security;

use Springy\Cookie;
use Springy\Security\Remember\InvalidRememberTokenException;
use Springy\Security\Remember\RememberTokenCredential;
use Springy\Security\Remember\RememberTokenManager;
use Springy\Security\Remember\RememberTokenStorageException;
use Springy\Session;

/**
 * Authentication class.
 */
class Authentication
{
    // The authentication driver.
    protected AuthDriverInterface $driver;
    // The current user object.
    protected ?IdentityInterface $user = null;
    // The "remember me" token manager. Without it the "remember me" feature is disabled.
    protected ?RememberTokenManager $rememberTokens = null;

    /**
     * Constructor.
     *
     * @param AuthDriverInterface       $driver
     * @param RememberTokenManager|null $rememberTokens
     */
    public function __construct(AuthDriverInterface $driver, ?RememberTokenManager $rememberTokens = null)
    {
        $this->setDriver($driver);
        $this->rememberTokens = $rememberTokens;

        $this->wakeupSession();
        $this->rememberSession();
    }

    /**
     * Wakes up the authenticated user from session.
     *
     * @return void
     */
    protected function wakeupSession(): void
    {
        $identitySessionData = Session::get($this->driver->getIdentitySessionKey());

        if (is_array($identitySessionData)) {
            $this->user = $this->driver->getDefaultIdentity();
            $this->user->fillFromSession($identitySessionData);
        }
    }

    /**
     * Restores user session from the "remember me" cookie if exists.
     *
     * The used token is replaced by a new one (rotation), so a stolen cookie
     * stops working as soon as the legitimate user comes back. The rotation is
     * atomic regarding RememberTokenManager::revokeAllFor(), so a concurrent
     * revocation can not be bypassed by the token issued here.
     *
     * When the token storage is unavailable the request goes on as anonymous
     * and the cookie is kept, so the session is restored once the storage
     * recovers.
     *
     * @return void
     */
    protected function rememberSession(): void
    {
        $cookieValue = Cookie::get($this->driver->getIdentitySessionKey());

        if ($this->user !== null || $this->rememberTokens === null || !is_string($cookieValue) || $cookieValue === '') {
            return;
        }

        try {
            [$identityId, $credential] = $this->rememberTokens->rotate($cookieValue);
        } catch (InvalidRememberTokenException) {
            $this->forgetRememberCookie();

            return;
        } catch (RememberTokenStorageException) {
            return;
        }

        $user = $this->driver->getIdentityById($identityId);

        if (!$user->isLoaded()) {
            $this->forgetRememberCookie();
            $this->revokeRememberToken($credential->toString());

            return;
        }

        $this->login($user);
        $this->saveRememberCookie($credential, $this->rememberTokens->getLifetime());
    }

    /**
     * Sets the authentication driver.
     *
     * @param AuthDriverInterface $driver
     *
     * @return void
     */
    public function setDriver(AuthDriverInterface $driver): void
    {
        $this->driver = $driver;
    }

    /**
     * Returns the authentication driver.
     *
     * @return \Springy\Security\AuthDriverInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Attempts to login with givens user and password credentials.
     *
     * @param string $login       the user login.
     * @param string $password    the user password.
     * @param bool   $remember    saves remember cookie (requires a RememberTokenManager).
     * @param bool   $saveSession saves in session if successful.
     *
     * @return bool
     */
    public function attempt(string $login, string $password, bool $remember = false, bool $saveSession = true): bool
    {
        if ($this->driver->isValid($login, $password)) {
            if ($saveSession) {
                $this->login($this->driver->getLastValidIdentity(), $remember);
            }

            return true;
        }

        return false;
    }

    /**
     * Validates the given credential without login.
     *
     * @param string $login
     * @param string $password
     *
     * @return bool
     */
    public function validate(string $login, string $password): bool
    {
        return $this->attempt($login, $password, false, false);
    }

    /**
     * Logs in the user and saves it into session.
     *
     * @param IdentityInterface $user
     * @param bool              $remember if true issues a "remember me" token into identity cookie.
     *                                    Ignored when no RememberTokenManager was given.
     *
     * The "remember me" token is issued before the session is written, so a
     * storage failure leaves the user logged out instead of half logged in.
     *
     * @return void
     *
     * @throws RememberTokenStorageException when the "remember me" token could not be issued.
     */
    public function login(IdentityInterface $user, bool $remember = false): void
    {
        $credential = $remember && $this->rememberTokens !== null
            ? $this->rememberTokens->issue((string) $user->getId())
            : null;

        $this->user = $user;

        Session::set($this->driver->getIdentitySessionKey(), $this->user->getSessionData());

        if ($credential !== null) {
            $this->saveRememberCookie($credential, $this->rememberTokens->getLifetime());
        }
    }

    /**
     * Logs in an user by givens id.
     *
     * @param mixed $uid
     * @param bool  $remember if true issues a "remember me" token into identity cookie.
     *
     * @return void
     */
    public function loginWithId(mixed $uid, bool $remember = false): void
    {
        $user = $this->driver->getIdentityById($uid);

        if ($user->isLoaded()) {
            $this->login($user, $remember);
        }
    }

    /**
     * Clears logged in user and its session.
     *
     * @return void
     */
    public function logout(): void
    {
        $this->user = null;

        $this->destroyUserData();
    }

    /**
     * Logs out the current user and invalidates all its "remember me" tokens.
     *
     * Other devices can no longer restore the session from their cookies, but
     * PHP sessions already open on them are not affected.
     *
     * The local logout is done first, so the current device is logged out even
     * if the revocation fails.
     *
     * @return void
     *
     * @throws RememberTokenStorageException when the tokens could not be revoked.
     */
    public function logoutAndRevokeRememberTokens(): void
    {
        $identityId = $this->user?->getId();

        $this->logout();

        if ($identityId !== null && $this->rememberTokens !== null) {
            $this->rememberTokens->revokeAllFor((string) $identityId);
        }
    }

    /**
     * Checks whether a user is logged in.
     *
     * @return bool
     */
    public function check(): bool
    {
        return !is_null($this->user);
    }

    /**
     * Returns current user.
     *
     * @return IdentityInterface|null
     */
    public function user(): ?IdentityInterface
    {
        return $this->user;
    }

    /**
     * Destroys the current logged in user session.
     *
     * The cookie is removed before the token revocation, which is best-effort.
     *
     * @return void
     */
    protected function destroyUserData(): void
    {
        Session::set($this->driver->getIdentitySessionKey(), null);
        Session::unregister($this->driver->getIdentitySessionKey());

        $cookieValue = Cookie::get($this->driver->getIdentitySessionKey());

        $this->forgetRememberCookie();

        if (is_string($cookieValue)) {
            $this->revokeRememberToken($cookieValue);
        }
    }

    /**
     * Revokes the "remember me" token kept in the cookie value, if possible.
     *
     * A storage failure is ignored: the client no longer holds the token and it
     * expires by itself.
     *
     * @param string $cookieValue
     *
     * @return void
     */
    protected function revokeRememberToken(string $cookieValue): void
    {
        if ($this->rememberTokens === null) {
            return;
        }

        try {
            $this->rememberTokens->revoke($cookieValue);
        } catch (RememberTokenStorageException) {
            return;
        }
    }

    /**
     * Saves the "remember me" credential into identity cookie.
     *
     * @param RememberTokenCredential $credential
     * @param int                     $lifetime
     *
     * @return void
     */
    protected function saveRememberCookie(RememberTokenCredential $credential, int $lifetime): void
    {
        Cookie::set(
            $this->driver->getIdentitySessionKey(),
            $credential->toString(),
            $lifetime,
            '/',
            config_get('system.session.domain'),
            config_get('system.session.secure'),
            true
        );
    }

    /**
     * Removes the "remember me" cookie from the client.
     *
     * @return void
     */
    protected function forgetRememberCookie(): void
    {
        Cookie::set(
            $this->driver->getIdentitySessionKey(),
            '',
            -3600,
            '/',
            config_get('system.session.domain'),
            config_get('system.session.secure'),
            true
        );
        Cookie::delete($this->driver->getIdentitySessionKey());
    }
}
