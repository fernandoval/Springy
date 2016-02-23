<?php
/** \file
 *  Springy.
 *
 *  \brief      Interface para padronizar as identidades que serão permissionadas na aplicação.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \author     Allan Marques - allan.marques@ymail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    0.1.1
 *  \ingroup    framework
 */
namespace Springy\Security;

interface AclUserInterface
{
    /**
     * \brief Retorna a permissão do usuário para a permissão indicada
     * \param [in] (string) $aclObjectName - Nome do objeto reprensentando a permissão.
     */
    public function getPermissionFor($aclObjectName);
}
