<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *  \copyright	Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 *  \copyright	Copyright (c) 2007-2015 Fernando Val\n
 *	\copyright Copyright (c) 2014 Allan Marques
 *
 *	\brief		Classe para gerenciamento de permissões de identidades autenticadas na aplicação
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	0.1
 *  \author		Allan Marques - allan.marques@ymail.com
 *	\ingroup	framework
 */
namespace FW\Security;

use FW\URI;
use FW\Kernel;

/**
 * \brief		Classe para gerenciamento de permissões de identidades autenticadas na aplicação
 */
class  AclManager
{    
    /// Nome do módulo no qual o usuário se encontra no request atual
    protected $module;
    /// Nome do controller no qual o usuário se encontra no request atual
    protected $controller;
    /// Nome da action na qual o usuário se encontra no request atual
    protected $action;
    /// Prefixo dos módulos
    protected $modulePrefix = 'admin/';
    /// Usuário atualmente autenticado no sistema
    protected $user;
    /// Caracter separador utilizado para concatenar o nome da permissão
    protected $separator = "|";
    /// Nome do módulo padrão, usado quando não estiver em nenhum módulo
    protected $defaultModule = 'default';
    
    /**
     * \brief Construtor da classe
     * \param [in] (\FW\Security\AclUserInterface) $user
     */
    public function __construct(AclUserInterface $user)
    {
        $this->user = $user;
        
        $this->setupCurrentAclObject();
    }
    
    /**
     * \brief Define em qual ação de permissão o usuário se encontra atualmente
     */
    public function setupCurrentAclObject()
    {
        $this->module = substr(Kernel::controllerNamespace(), strlen($this->modulePrefix)) or $this->defaultModule;        
        $this->controller = URI::getControllerClass();
        $this->action = URI::getSegment(0);
    }
    
    /**
     * \brief Retorna o módulo sendo acessado no request atual
     * \return (string)
     */
    public function getCurrentModule()
    {
        return $this->module;
    }
    
    /**
     * \brief Retorna o controller sendo acessado no request atual
     * \return (string)
     */
    public function getCurrentController()
    {
        return $this->controller;
    }
    
    /**
     * \brief Retorna a ação sendo acessada no request atual
     * \return (string)
     */
    public function getCurrentAction()
    {
        return $this->action;
    }
    
    /**
     * \brief Seta o prefixo dos módulos
     * \param [in] (string) $modulePrefix
     */
    public function setModulePrefix($modulePrefix)
    {
        $this->modulePrefix = $modulePrefix;
    }
    
    /**
     * \brief Retorna o prefixo dos módulos
     * \return (string)
     */
    public function getModulePrefix()
    {
        return $this->modulePrefix;
    }
    
    /**
     * \brief Seta o separador do nome da permissão
     * \param [in] (string) $separator
     */
    public function setSeparator($separator)
    {
        $this->separator = $separator;
    }
    
    /**
     * \brief Retorna o separador do nome da permissão
     * \return (string)
     */
    public function getSeparator()
    {
        return $this->separator;
    }
    
    /**
     * \brief Seta o nome do módulo padrão
     * \param [in] (string) $module
     */
    public function setDefaultModule($module)
    {
        $this->defaultModule = $module;
    }
    
    /**
     * \brief Retorna o nome do módulo padrão
     * \return (string)
     */
    public function getDefaultModule()
    {
        return $this->defaultModule;
    }
    
    /**
     * \brief Seta o usuário 
     * \param [in] (\FW\Security\AclUserInterface) $user
     */
    public function setAclUser(AclUserInterface $user)
    {
        $this->user = $user;
    }
    
    /**
     * \brief Retorna o usuário
     * \return (\FW\Security\AclUserInterface)
     */
    public function getAclUser()
    {
        return $this->user;
    }

    /**
     * \brief Verifica se o usuário atual tem permissão à acessar o recurso atual
     * \return (boolean)
     */
    public function isPermitted()
    {
        return (bool)$this->user->getPermissionFor( $this->getAclObjectName() );
    }
    
    /**
     * \brief Retorna o nome do recurso atual, equivalente à permissão
     * \return (string)
     */
    public function getAclObjectName()
    {
        return implode($this->separator, array($this->module, $this->controller, $this->action));
    }
}