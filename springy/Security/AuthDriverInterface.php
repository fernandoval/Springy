<?php

/**
 * Interface to standardize identity authentication drivers.
 *
 * @copyright 2016 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 */

namespace Springy\Security;

interface AuthDriverInterface
{
    /**
     * Returns the session identifier name of the identity.
     *
     * @return string
     */
    public function getIdentitySessionKey(): string;

    /**
     * Checks if the login and password of the current identity are valid.
     *
     * @param string $login
     * @param string $password
     *
     * @return bool
     */
    public function isValid(string $login, string $password): bool;

    /**
     * Sets the identity that will be the default type to perform the authentication.
     *
     * @param IdentityInterface $identity
     *
     * @return void
     */
    public function setDefaultIdentity(IdentityInterface $identity): void;

    /**
     * Returns the identity type to perform the authentication.
     *
     * @return IdentityInterface
     */
    public function getDefaultIdentity(): IdentityInterface;

    /**
     * Returns the last identity to successfully pass authentication.
     *
     * @return IdentityInterface|null null if no identity has been successfully authenticated yet.
     */
    public function getLastValidIdentity(): ?IdentityInterface;

    /**
     * Returns the identity by the ID that identifies it.
     *
     * @param mixed $iid
     *
     * @return IdentityInterface
     */
    public function getIdentityById($iid): IdentityInterface;
}
