<?php

/**
 * Access Control List (ACL) Authorization for web application.
 *
 * @copyright 2014 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   1.3.0
 */

namespace Springy\Security;

use Springy\Kernel;
use Springy\URI;

class AclManager
{
    /// Nome do módulo no qual o usuário se encontra no request atual
    protected $module;
    /// Nome do controller no qual o usuário se encontra no request atual
    protected $controller;
    /// Nome da action na qual o usuário se encontra no request atual
    protected $action;
    /// Prefixo dos módulos
    protected $modulePrefix = '';
    /** @var IdentityInterface the current user object */
    protected $user;
    /// Caracter separador utilizado para concatenar o nome da permissão
    protected $separator = '|';
    /// Nome do módulo padrão, usado quando não estiver em nenhum módulo
    protected $defaultModule = 'default';

    /**
     * Constructor.
     *
     * @param IdentityInterface $user
     */
    public function __construct(IdentityInterface $user)
    {
        $this->user = $user;

        $this->setupCurrentAclObject();
    }

    /**
     * Defines current ACL object.
     *
     * @return void
     */
    public function setupCurrentAclObject(): void
    {
        $prefix = explode(
            '/',
            substr(
                implode(
                    '/',
                    array_slice(
                        URI::getAllSegments(),
                        count(URI::getAllIgnoredSegments()),
                        URI::getSegmentPage()
                    )
                ),
                strlen(Kernel::controllerNamespace())
            )
        );
        array_unshift($prefix, $this->modulePrefix ?: Kernel::controllerNamespace());

        $this->module = implode($this->separator, array_filter($prefix));
        $this->controller = URI::getControllerClass();
        $this->action = implode(
            $this->separator,
            array_slice(
                URI::getAllSegments(),
                URI::getSegmentPage() + 1
            )
        );
    }

    /**
     * Gets the current module name.
     *
     * @return string
     */
    public function getCurrentModule()
    {
        return $this->module;
    }

    /**
     * Gets current controller name.
     *
     * @return string
     */
    public function getCurrentController()
    {
        return $this->controller;
    }

    /**
     * Gets current action string.
     *
     * @return string
     */
    public function getCurrentAction()
    {
        return $this->action;
    }

    /**
     * Defines the movule prefix string.
     *
     * @param string $modulePrefix
     *
     * @return void
     */
    public function setModulePrefix($modulePrefix)
    {
        $this->modulePrefix = $modulePrefix;
    }

    /**
     * Gets the module prefix string.
     *
     * @return string
     */
    public function getModulePrefix()
    {
        return $this->modulePrefix;
    }

    /**
     * Defines the separator character to build ACL string.
     *
     * @param string $separator
     *
     * @return void
     */
    public function setSeparator($separator)
    {
        $this->separator = $separator;
    }

    /**
     * Gets the separator character used to build the ACL string.
     *
     * @return string
     */
    public function getSeparator()
    {
        return $this->separator;
    }

    /**
     * Defines the default module name.
     *
     * @param string $module
     *
     * @return void
     *
     * @deprecated
     */
    public function setDefaultModule($module)
    {
        $this->defaultModule = $module;
    }

    /**
     * Gets the default module name.
     *
     * @return string
     *
     * @deprecated
     */
    public function getDefaultModule()
    {
        return $this->defaultModule;
    }

    /**
     * Defines the user object.
     *
     * @param IdentityInterface $user
     *
     * @return void
     */
    public function setAclUser(IdentityInterface $user)
    {
        $this->user = $user;
    }

    /**
     * Gets the user object.
     *
     * @return IdentityInterface object
     */
    public function getAclUser()
    {
        return $this->user;
    }

    /**
     * Checks whether the current user has permission to access current resource.
     *
     * @return bool
     */
    public function isPermitted()
    {
        return $this->user->hasPermissionFor($this->getAclObjectName());
    }

    /**
     * Gets the ACL string.
     *
     * @return string
     */
    public function getAclObjectName()
    {
        return implode($this->separator, [$this->module, $this->controller, $this->action]);
    }
}
