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
        }

        $user = $this->driver->getIdentityById($identityId);

        if (!$user->isLoaded()) {
            $this->rememberTokens->revoke($credential->toString());
            $this->forgetRememberCookie();

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
     * @return void
     */
    public function login(IdentityInterface $user, bool $remember = false): void
    {
        $this->user = $user;

        Session::set($this->driver->getIdentitySessionKey(), $this->user->getSessionData());

        if ($remember && $this->rememberTokens !== null) {
            $this->rememberUser($this->rememberTokens);
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
     * Logs out the current user and invalidates all its "remember me" tokens in every device.
     *
     * @return void
     */
    public function logoutFromAllDevices(): void
    {
        if ($this->user !== null && $this->rememberTokens !== null) {
            $this->rememberTokens->revokeAllFor((string) $this->user->getId());
        }

        $this->logout();
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
     * @return void
     */
    protected function destroyUserData(): void
    {
        Session::set($this->driver->getIdentitySessionKey(), null);
        Session::unregister($this->driver->getIdentitySessionKey());

        $cookieValue = Cookie::get($this->driver->getIdentitySessionKey());

        if ($this->rememberTokens !== null && is_string($cookieValue)) {
            $this->rememberTokens->revoke($cookieValue);
        }

        $this->forgetRememberCookie();
    }

    /**
     * Issues a new "remember me" token for the current user and saves it into identity cookie.
     *
     * @param RememberTokenManager $rememberTokens
     *
     * @return void
     */
    protected function rememberUser(RememberTokenManager $rememberTokens): void
    {
        $this->saveRememberCookie(
            $rememberTokens->issue((string) $this->user->getId()),
            $rememberTokens->getLifetime()
        );
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
