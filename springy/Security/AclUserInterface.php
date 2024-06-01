<?php

/**
 * ACL identity user interface.
 *
 * @copyright 2018 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @deprecated 4.6.0
 *
 * @version    0.1.4
 */

namespace Springy\Security;

/**
 * ACL identity user interface.
 */
interface AclUserInterface
{
    /**
     * Get the user permission for the given ACL.
     *
     * @param string $aclObjectName the name of the ACL.
     *
     * @return bool True if the user has permission to access or false if not.
     */
    public function getPermissionFor($aclObjectName);
}
