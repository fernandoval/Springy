<?php

/**
 * Authentication driver for database storace.
 *
 * @copyright 2014 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Allan Marques <allan.marques@ymail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version 1.0.0
 */

namespace Springy\Security;

use Springy\Core\Application;

/**
 * Database Authentication Driver class.
 */
class DBAuthDriver implements AuthDriverInterface
{
    /** @var HasherInterface hasher generator */
    protected $hasher;
    /** @var IdentityInterface identity class */
    protected $identity;
    /** @var IdentityInterface last valid identity */
    protected $lastValidIdentity;

    /**
     * Constructor.
     *
     * @param HasherInterface   $hasher
     * @param IdentityInterface $identity
     */
    public function __construct(HasherInterface $hasher = null, IdentityInterface $identity = null)
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
     * @return Springy\Security\HasherInterface
     */
    public function getHasher()
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
     * @return Springy\Security\IdentityInterface
     */
    public function getIdentityById($iid)
    {
        $idField = $this->identity->getIdField();
        $this->identity->loadByCredentials([$idField => $iid]);

        return $this->identity;
    }

    /**
     * Returns last valid identity.
     *
     * @return Springy\Security\IdentityInterface
     */
    public function getLastValidIdentity()
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
    public function isValid($login, $password): bool
    {
        $appInstance = Application::sharedInstance();
        $appInstance->fire('auth.attempt', [$login, $password]);

        $credentials = $this->identity->getCredentials();
        $this->identity->loadByCredentials([$credentials['login'] => $login]);
        $validPassword = $this->identity->{$credentials['password']};

        if ($this->hasher->verify($password, $validPassword)) {
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
     * @return Springy\Security\IdentityInterface
     */
    public function getDefaultIdentity()
    {
        return $this->identity;
    }
}
