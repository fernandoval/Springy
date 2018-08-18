<?php
/** \file
 *  Springy.
 *
 *  @brief      Interface para padronizar as identidades que serão permissionadas na aplicação.
 *
 *  @copyright  Copyright (c) 2007-2018 Fernando Val
 *  @author     Allan Marques - allan.marques@ymail.com
 *  @author     Fernando Val - fernando.val@gmail.com
 *
 *  @version    0.1.1.2
 *  @ingroup    framework
 */

namespace Springy\Security;

/**
 *  ACL identity user interface.
 */
interface AclUserInterface
{
    /**
     *  Get the user permission for the given ACL.
     *
     *  @param string $aclObjectName the name of the ACL.
     *
     *  @return bool True if the user has permission to access or false if not.
     */
    public function getPermissionFor($aclObjectName);
}
