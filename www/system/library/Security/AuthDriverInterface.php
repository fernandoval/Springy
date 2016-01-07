<?php
/**	\file
 *	FVAL PHP Framework for Web Applications.
 *
 *  \copyright	Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 *  \copyright	Copyright (c) 2007-2015 Fernando Val\n
 *	\copyright Copyright (c) 2014 Allan Marques
 *
 *	\brief		Interface para padronizar os drivers de autenticação de identidades
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	0.1
 *  \author		Allan Marques - allan.marques@ymail.com
 *	\ingroup	framework
 */
namespace FW\Security;

/**
 * \brief Interface para padronizar os drivers de autenticação de identidades.
 */
interface AuthDriverInterface
{
    /**
     * \brief Retorna o o nome identificador da sessão da identidade
     * \return (string).
     */
    public function getIdentitySessionKey();

    /**
     * \brief Verifica se o login e o password da identidade atual são válidos
     * \param [in] (string) $login - Login da identidade
     * \param [in] (string) $password - Senha da identidade
     * return (bool).
     */
    public function isValid($login, $password);

    /**
     * \brief Seta a identidade que será o tipo padrão para realizar a autenticação
     * \param [in] (\FW\Security\IdentityInterface) $identity - Tipo padrão de identidade.
     */
    public function setDefaultIdentity(IdentityInterface $identity);

    /**
     * \brief Retorna a identidade tipo padrão para realizar a autenticação
     * \return (\FW\Security\IdentityInterface).
     */
    public function getDefaultIdentity();

    /**
     * \brief Retorna a última identidade a passar com sucesso pela autenticação
     * \return (\FW\Security\IdentityInterface).
     */
    public function getLastValidIdentity();

    /**
     * \brief Retorna a identidade pelo ID que à identifica
     * \return (\FW\Security\IdentityInterface).
     */
    public function getIdentityById($id);
}
