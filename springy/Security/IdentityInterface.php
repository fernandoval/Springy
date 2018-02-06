<?php
/** @file
 *  Springy.
 *
 *  @brief      Interface para representar identidades que terão uma sessão na aplicação.
 *
 *  @copyright  (c) 2007-2018 Fernando Val
 *  @author     Allan Marques - allan.marques@ymail.com
 *  @author     Fernando Val - fernando.val@gmail.com
 *
 *  @version    0.2.2.4
 *  @ingroup    framework
 */

namespace Springy\Security;

/**
 *  Session identity interface.
 */
interface IdentityInterface
{
    /**
     *  Loads the identity data by given credential.
     *
     *  This method is executed when the user is loaded by a given array of conditions for a query.
     *
     *  @param array $data the array with the condition to load the data.
     *
     *  @return void
     */
    public function loadByCredentials(array $data);

    /**
     *  Load the identity class from the session.
     *
     *  @param array $data the array with the identity data.
     *
     *  @return void
     */
    public function fillFromSession(array $data);

    /**
     *  Get the identity id key.
     *
     *  @return string the identity id key
     */
    public function getId();

    /**
     *  Get the identity id column name.
     *
     *  @return string the column name for the identity id key.
     */
    public function getIdField();

    /**
     *  Get the session key name for the identity.
     *
     *  @return string the session key name for the identity.
     */
    public function getSessionKey();

    /**
     *  Get the identity session data.
     *
     *  @return array the array with data to be saved in identity session.
     */
    public function getSessionData();

    /**
     *  Get the identity credentials.
     *
     *  @example Login and password.
     *
     *  @return array the array with credential data.
     */
    public function getCredentials();
}
