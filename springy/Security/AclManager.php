<?php
/**
 * ACL (Access Control List) Authorization class for the application.
 *
 * @copyright 2014 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   0.3.0.4
 */

namespace Springy\Security;

use Springy\Kernel;
use Springy\URI;

/**
 * ACL (Access Control List) Authorization class for the application.
 */
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
    /// Current user object
    protected $user;
    /// Caracter separador utilizado para concatenar o nome da permissão
    protected $separator = '|';
    /// Nome do módulo padrão, usado quando não estiver em nenhum módulo
    protected $defaultModule = 'default';

    /**
     * Constructor.
     *
     * @param AclUserInterface $user
     */
    public function __construct(AclUserInterface $user)
    {
        $this->user = $user;

        $this->setupCurrentAclObject();
    }

    /**
     * Defines current ACL object.
     *
     * @return void
     */
    public function setupCurrentAclObject()
    {
        $this->module = substr(Kernel::controllerNamespace(), strlen($this->modulePrefix)) or $this->defaultModule;
        $this->controller = URI::getControllerClass();
        // $this->action = URI::getSegment(0);

        $segments = [];
        $ind = 0;
        do {
            $segment = URI::getSegment($ind++);
            if ($segment !== false) {
                $segments[] = $segment;
            }
        } while ($segment !== false);

        $this->action = implode($this->separator, $segments);
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
     * @return string
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
     */
    public function setDefaultModule($module)
    {
        $this->defaultModule = $module;
    }

    /**
     * Gets the default module name.
     *
     * @return string
     */
    public function getDefaultModule()
    {
        return $this->defaultModule;
    }

    /**
     * Defines the user object.
     *
     * @param AclUserInterface $user
     *
     * @return void
     */
    public function setAclUser(AclUserInterface $user)
    {
        $this->user = $user;
    }

    /**
     * Gets the user object.
     *
     * @return AclUserInterface object
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
        return (bool) $this->user->getPermissionFor($this->getAclObjectName());
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
