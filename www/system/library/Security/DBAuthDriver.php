<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *  \copyright	Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 *  \copyright	Copyright (c) 2007-2015 Fernando Val\n
 *	\copyright Copyright (c) 2014 Allan Marques
 *
 *	\brief		Driver de autenticação que utiliza o banco de dados como storage
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	0.1
 *  \author		Allan Marques - allan.marques@ymail.com
 *	\ingroup	framework
 */
namespace FW\Security;

use FW\Core\Application;

/**
 * \brief		Driver de autenticação que utiliza o banco de dados como storage
 */
class DBAuthDriver implements AuthDriverInterface
{
    /// Gerador de hashes do autenticador
    protected $hasher;
    /// Identidade padrão para verificação
    protected $identity;
    /// Ultima identidade válida 
    protected $lastValidIdentity;

    /**
     * \brief COnstrutor da classe
     * \param [in] (\FW\Security\HasherInterface) $hasher
     * \param [in] (\FW\Security\IdentityInterface) $identity
     */
    public function __construct(HasherInterface $hasher = null, IdentityInterface $identity = null)
    {
        $this->setHasher($hasher);
        $this->setDefaultIdentity($identity);
    }
    
    /**
     * \brief Seta o hasher da senha de autenticação
     * \param [in] (\FW\Security\HasherInterface) $hasher
     */
    public function setHasher(HasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }
    
    /**
     * \brief Retorna o hasher da senha de autenticação
     * @return (\FW\Security\HasherInterface)
     */
    public function getHasher()
    {
        return $this->hasher;
    }
    
    /**
     * \brief Seta a identidade que será o tipo padrão para realizar a autenticação
     * \param [in] (\FW\Security\IdentityInterface) $identity - Tipo padrão de identidade
     */
    public function setDefaultIdentity(IdentityInterface $identity)
    {
        $this->identity = $identity;
    }
    
    /**
     * \brief Retorna a identidade pelo ID que à identifica
     * \return (\FW\Security\IdentityInterface)
     */
    public function getIdentityById($id)
    {
        $idField = $this->identity->getIdField();
        $this->identity->loadByCredencials( array($idField => $id) );
        
        return $this->identity;
    }

    /**
     * \brief Retorna a última identidade a passar com sucesso pela autenticação
     * \return (\FW\Security\IdentityInterface)
     */
    public function getLastValidIdentity() 
    {
        return $this->lastValidIdentity;
    }

    /**
     * \brief Retorna o o nome identificador da sessão da identidade
     * \return (string)
     */
    public function getIdentitySessionKey() 
    {
        return $this->identity->getSessionKey();
    }

    /**
     * \brief Verifica se o login e o password da identidade atual são válidos
     * \param [in] (string) $login - Login da identidade
     * \param [in] (string) $password - Senha da identidade
     * return (bool)
     */
    public function isValid($login, $password)
    {
        $appInstance = Application::sharedInstance();        
        $appInstance->fire('auth.attempt', array($login, $password));
        
        $credentials = $this->identity->getCredentials();        
        $this->identity->loadByCredencials( array($credentials['login'] => $login) );        
        $validPassword = $this->identity->{$credentials['password']};
        
        if ( $this->hasher->verify($password, $validPassword) ) {
            $this->lastValidIdentity = clone $this->identity;
            
            $appInstance->fire('auth.success', array($this->lastValidIdentity));
            
            return true;
        }
        
        $appInstance->fire('auth.fail', array($login, $password));
        
        return false;
    }

    /**
     * \brief Retorna a identidade tipo padrão para realizar a autenticação
     * \return (\FW\Security\IdentityInterface)
     */
    public function getDefaultIdentity() 
    {
        return $this->identity;
    }
}
