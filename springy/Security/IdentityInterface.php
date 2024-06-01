<?php

/**
 * Session identity interface.
 *
 * @copyright 2018 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   1.0.0
 */

namespace Springy\Security;

interface IdentityInterface
{
    /**
     * Loads the identity class from the session.
     *
     * @param array $data the array with the identity data.
     *
     * @return void
     */
    public function fillFromSession(array $data): void;

    /**
     * Gets the identity id key.
     *
     * @return mixed the identity id key
     */
    public function getId(): mixed;

    /**
     * Gets the identity id column name.
     *
     * @return string the column name for the identity id key.
     */
    public function getIdField(): string;

    /**
     * Gets the session key name for the identity.
     *
     * @return string the session key name for the identity.
     */
    public function getSessionKey(): string;

    /**
     * Gets the identity session data.
     *
     * @return array the array with data to be saved in identity session.
     */
    public function getSessionData(): array;

    /**
     * Gets the identity credentials.
     *
     * @example Login and password.
     *
     * @return array the array with credential data.
     */
    public function getCredentials(): array;

    /**
     * Get the user permission for the given ACL.
     *
     * @param string $aclObjectName the name of the ACL.
     *
     * @return bool True if the user has permission to access or false if not.
     */
    public function hasPermissionFor(string $aclObjectName): bool;

    /**
     * Returns true if the user's data was loaded.
     *
     * @return bool
     */
    public function isLoaded(): bool;

    /**
     * Loads the identity data by given credential.
     *
     * This method is executed when the user is loaded by a given array of conditions for a query.
     *
     * @param array $data the array with the condition to load the data.
     *
     * @return void
     */
    public function loadByCredentials(array $data): void;
}
