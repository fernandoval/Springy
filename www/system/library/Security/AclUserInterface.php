<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *	\copyright Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.
 *	\copyright Copyright (c) 2007-2014 Fernando Val
 *	\copyright Copyright (c) 2014 Allan Marques
 *
 *	\brief		Interface para padronizar as identidades que serão permissionadas na aplicação
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	0.1
 *  \author		Allan Marques - allan.marques@ymail.com
 *	\ingroup	framework
 */
namespace FW\Security;

interface AclUserInterface 
{
    /**
     * \brief Retorna a permissão do usuário para a permissão indicada
     * \param [in] (string) $aclObjectName - Nome do objeto reprensentando a permissão
     */
    public function getPermissionFor($aclObjectName);
}
