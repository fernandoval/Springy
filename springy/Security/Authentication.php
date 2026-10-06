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

    /**
     * Constructor.
     *
     * @param AuthDriverInterface $driver
     */
    public function __construct(?AuthDriverInterface $driver = null)
    {
        $this->setDriver($driver);

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
     * Restores user session from identity cookie if exists.
     *
     * @return void
     */
    protected function rememberSession(): void
    {
        $uid = Cookie::get($this->driver->getIdentitySessionKey());

        if (is_null($this->user) && !empty($uid)) {
            $this->loginWithId($uid);
        }
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
     * @param bool   $remember    saves remember cookie.
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
     * @param bool              $remember if true saves the user id into identity cookie.
     *
     * @return void
     */
    public function login(IdentityInterface $user, bool $remember = false): void
    {
        $this->user = $user;

        Session::set($this->driver->getIdentitySessionKey(), $this->user->getSessionData());

        if ($remember) {
            Cookie::set(
                $this->driver->getIdentitySessionKey(), //Chave do cookie
                $this->user->getId(), //Id do usuário
                5184000, //60 dias
                '/',
                config_get('system.session.domain'),
                config_get('system.session.secure'),
                true
            );
        }
    }

    /**
     * Logs in an user by givens id.
     *
     * @param mixed $uid
     * @param bool  $remember if true saves the user id into identity cookie.
     *
     * @return void
     */
    public function loginWithId(mixed $uid, bool $remember = false): void
    {
        $user = $this->driver->getIdentityById($uid);

        if ($user) {
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

        Cookie::set(
            $this->driver->getIdentitySessionKey(),
            '',
            time() - 3600,
            '/',
            config_get('system.session.domain'),
            config_get('system.session.secure'),
            true
        );
        Cookie::delete($this->driver->getIdentitySessionKey());
    }
}
