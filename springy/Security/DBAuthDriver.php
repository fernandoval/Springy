<?php

/**
 * Authentication driver for database storace.
 *
 * @copyright 2014 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Allan Marques <allan.marques@ymail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 */

namespace Springy\Security;

use Springy\Core\Application;

/**
 * Database Authentication Driver class.
 */
class DBAuthDriver implements AuthDriverInterface
{
    /** The hasher generator */
    protected HasherInterface $hasher;
    /** The identity class */
    protected IdentityInterface $identity;
    /** Last valid identity */
    protected ?IdentityInterface $lastValidIdentity = null;

    public function __construct(HasherInterface $hasher, IdentityInterface $identity)
    {
        $this->setHasher($hasher);
        $this->setDefaultIdentity($identity);
    }

    /**
     * Sets the hasher class.
     *
     * @param HasherInterface $hasher
     *
     * @return void
     */
    public function setHasher(HasherInterface $hasher): void
    {
        $this->hasher = $hasher;
    }

    /**
     * Returns the current hasher.
     *
     * @return HasherInterface
     */
    public function getHasher(): HasherInterface
    {
        return $this->hasher;
    }

    /**
     * Sets the default identity driver.
     *
     * @param IdentityInterface $identity
     *
     * @return void
     */
    public function setDefaultIdentity(IdentityInterface $identity): void
    {
        $this->identity = $identity;
    }

    /**
     * Returns the identity by its id.
     *
     * @param mixed $iid
     *
     * @return IdentityInterface
     */
    public function getIdentityById($iid): IdentityInterface
    {
        $idField = $this->identity->getIdField();
        $this->identity->loadByCredentials([$idField => $iid]);

        return $this->identity;
    }

    /**
     * Returns last valid identity.
     *
     * @return IdentityInterface|null null if no identity has been successfully authenticated yet.
     */
    public function getLastValidIdentity(): ?IdentityInterface
    {
        return $this->lastValidIdentity;
    }

    /**
     * Returns the identity session key.
     *
     * @return string
     */
    public function getIdentitySessionKey(): string
    {
        return $this->identity->getSessionKey();
    }

    /**
     * Checks whether given credentials is a valid user.
     *
     * @param string $login
     * @param string $password
     *
     * @return bool
     */
    public function isValid(string $login, string $password): bool
    {
        $appInstance = Application::sharedInstance();
        $appInstance->fire('auth.attempt', [$login, $password]);

        $credentials = $this->identity->getCredentials();
        $this->identity->loadByCredentials([$credentials['login'] => $login]);
        $validPassword = $this->identity->{$credentials['password']};

        if (!is_null($validPassword) && $this->hasher->verify($password, $validPassword)) {
            $this->lastValidIdentity = clone $this->identity;

            $appInstance->fire('auth.success', [$this->lastValidIdentity]);

            return true;
        }

        $appInstance->fire('auth.fail', [$login, $password]);

        return false;
    }

    /**
     * Returns the default identity driver.
     *
     * @return IdentityInterface
     */
    public function getDefaultIdentity(): IdentityInterface
    {
        return $this->identity;
    }
}
