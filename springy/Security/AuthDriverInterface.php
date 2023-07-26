<?php

/**
 * Interface to standardize identity authentication drivers.
 *
 * @copyright 2016 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   0.1.3
 */

namespace Springy\Security;

interface AuthDriverInterface
{
    /**
     * Returns the session identifier name of the identity.
     *
     * @return string
     */
    public function getIdentitySessionKey();

    /**
     * Checks if the login and password of the current identity are valid.
     *
     * @param string $login
     * @param string $password
     *
     * @return bool
     */
    public function isValid($login, $password);

    /**
     * Sets the identity that will be the default type to perform the authentication.
     *
     * @param IdentityInterface $identity
     *
     * @return void
     */
    public function setDefaultIdentity(IdentityInterface $identity);

    /**
     * Returns the identity type to perform the authentication.
     *
     * @return \Springy\Security\IdentityInterface
     */
    public function getDefaultIdentity();

    /**
     * Returns the last identity to successfully pass authentication.
     *
     * @return \Springy\Security\IdentityInterface
     */
    public function getLastValidIdentity();

    /**
     * Returns the identity by the ID that identifies it.
     *
     * @param string $iid
     *
     * @return \Springy\Security\IdentityInterface
     */
    public function getIdentityById($iid);
}
