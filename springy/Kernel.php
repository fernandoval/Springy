<?php

/**
 * Framework kernel.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Lucas Cardozo <lucas.cardozo@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version    2.5.1
 */

namespace Springy;

/**
 * Framework kernel class.
 *
 * This is a static class and can not be instantiated by user.
 */
class Kernel
{
    /// Versão do framework
    const VERSION = '4.3.1';

    /// Path constants
    const PATH_PROJECT = 'PROJ';
    const PATH_CONF = 'CONF';
    const PATH_APPLICATION = 'APP';
    const PATH_VAR = 'VAR';
    const PATH_CLASSES = 'CLASSES';
    const PATH_CONTROLLER = 'CONTROLLER';
    const PATH_LIBRARY = 'LIB';
    const PATH_ROOT = 'ROOT';
    const PATH_WEB_ROOT = 'ROOT';
    const PATH_VENDOR = 'VENDOR';
    const PATH_MIGRATION = 'MIGRATION';
    /// Path constants to back compatibility
    const PATH_CONFIGURATION = self::PATH_CONF;
    const PATH_SYSTEM = self::PATH_APPLICATION;
    const PATH_CLASS = self::PATH_CLASSES;

    /// Start time
    private static $startime = null;
    /// Determina o root de controladoras
    private static $controller_root = [];
    /// Caminho do namespace do controller
    private static $controller_namespace = null;
    /// The controller file path name
    private static $controllerFile = null;
    /// The controller file class name
    private static $controllerName = null;
    /// Run global pre-controller switch
    private static $runGlobal = true;

    /// System environment
    private static $environment = '';
    /// System name
    private static $name = 'System Name';
    /// System version
    private static $version = [0, 0, 0];
    /// Project code name
    private static $projName = '';
    /// System path
    private static $paths = [];
    /// System charset
    private static $charset = 'UTF-8';
    /// CGI execution mode
    private static $cgiMode = false;

    /// List of ignored errors
    private static $ignoredErrors = [];
    /// List of error hook functions
    private static $errorHooks = [];

    /// Default template vars
    private static $templateVars = [];
    /// Default template functions
    private static $templateFuncs = [];

    /**
     * Calls the application controller if defined.
     *
     * @return void
     */
    private static function _callController()
    {
        // Has a controller defined?
        if (is_null(self::$controllerName)) {
            return;
        }

        // Call hook controller
        self::_callHookController();

        // Controller class exists?
        if (!class_exists(self::$controllerName)) {
            new Errors(404, 'No ' . self::$controllerName . ' on ' . self::$controllerFile);
        }

        /// The controller
        $controller = new self::$controllerName();
        /// The controller function
        $pageMethod = self::_getControllerMethod($controller);

        // The page method can be executed?
        if (is_callable([$controller, $pageMethod])) {
            call_user_func([$controller, $pageMethod]);
        }

        // Print out the debug
        Core\Debug::printOut();
    }

    /**
     * Starts the global pre-controller hook if needed.
     *
     * @return void
     */
    private static function _callGlobal()
    {
        // The global is disabled?
        if (!self::$runGlobal) {
            return;
        }

        /// Global pre-controller file path name
        $globalPath = self::path(self::PATH_CONTROLLER) . DIRECTORY_SEPARATOR . '_global.php';

        // The global pre-controller exists?
        if (!file_exists($globalPath)) {
            return;
        }

        // Load the global pre-controller file
        require_once $globalPath;
        if (class_exists('Global_Controller')) {
            // Create the global pre-controller class
            new \Global_Controller();
        }
    }

    /**
     * Calls the hook controller if exists.
     *
     * @return void
     */
    private static function _callHookController()
    {
        /// The hook controller file name
        $defaultFile = dirname(self::$controllerFile) . DIRECTORY_SEPARATOR . '_default.php';

        // Hook controller exists?
        if (!file_exists($defaultFile)) {
            return;
        }

        // Load the hook controller file
        require $defaultFile;
        if (class_exists('Default_Controller')) {
            new \Default_Controller();
        }
    }

    /**
     * Executes a framework special command.
     *
     * @return void
     */
    private static function _callSpecialCommands()
    {
        // Has a controller?
        if (!is_null(self::$controllerName) || self::$cgiMode) {
            return;
        }

        /// The command
        $command = URI::getSegment(0, false);

        switch ($command) {
            case '_':
            case '_springy_':
                new Core\Copyright(true);

                return;
            case '_pi_':
                phpinfo();
                ob_end_flush();

                return;
            case '_error_':
                self::_testError();

                break;
            case '_system_bug_':
            case '_system_bug_solved_':
                self::_systemBugPage($command);

                return;
            case '__migration__':
                // Cli mode only
                if (!defined('STDIN')) {
                    echo 'This script can be executed only in CLI mode.';
                    exit(998);
                }

                $controller = new Migrator();
                $controller->run();

                return;

        }

        new Errors(404, 'Page not found');
    }

    /**
     * Checks if has a developer accessing for debug.
     *
     * @return void
     */
    private static function _checkDevAccessDebug()
    {
        // Has a developer credential?
        $devUser = Configuration::get('system', 'developer_user');
        $devPass = Configuration::get('system', 'developer_pass');
        if (!$devUser || !$devPass) {
            return;
        }

        // Has developer credential access in query string?
        if (URI::getParam($devUser) == $devPass) {
            Cookie::set('_developer', true);
        } elseif (URI::getParam($devUser) == 'off') {
            // Turning off dev debug access?
            Cookie::delete('_developer');
        }

        // Has a dev cookie?
        if (Cookie::exists('_developer')) {
            Configuration::set('system', 'maintenance', false);
            Configuration::set('system', 'debug', true);
        }

        // Has a DBA credential?
        $dbaUser = Configuration::get('system', 'dba_user');
        if (!Configuration::get('system', 'debug') || !$dbaUser) {
            return;
        }

        // Has DBA credential access in query string?
        if (URI::getParam($dbaUser) == $devPass) {
            Cookie::set('_dba', true);
        } elseif (URI::getParam($dbaUser) == 'off') {
            // Turning off DBA debug access?
            Cookie::delete('_dba');
        }

        // Has a DBA cookie?
        if (Cookie::exists('_dba')) {
            Configuration::set('system', 'sql_debug', true);
        }
    }

    /**
     * Defines the application controller.
     *
     * @return void
     */
    private static function _defineController()
    {
        // Get controller file name
        self::$controllerFile = URI::parseURI();
        if (is_null(self::$controllerFile)) {
            return;
        }

        // Validate the URI before load the controller
        URI::validateURI();

        // Load the controller file
        require_once self::$controllerFile;

        // Define o nome da classe controladora
        self::$controllerName = str_replace('-', '_', URI::getControllerClass()) . '_Controller';

        // Bypass the global pre-controller?
        if (defined('BYPASS_CONTROLLERS') || method_exists(self::$controllerName, '_ignore_global')) {
            self::$runGlobal = false;
        }
    }

    /**
     * Gets the name of method to be called in controller.
     *
     * @param object $controller the controller.
     *
     * @return string The name of method.
     */
    private static function _getControllerMethod($controller)
    {
        $pageMethod = str_replace('-', '_', URI::getSegment(0, true));

        if (!$pageMethod || !is_callable([$controller, $pageMethod])) {
            $pageMethod = '_default';
        }

        return $pageMethod;
    }

    /**
     * Verifies if HTTP authentication is required.
     *
     * @return void
     */
    private static function _httpAuthNeeded()
    {
        $auth = Configuration::get('system', 'authentication');

        // HTTP authentication credential not defined?
        if (defined('STDIN') || !is_array($auth) || !isset($auth['user']) || !isset($auth['pass']) || Cookie::get('__sys_auth__')) {
            return;
        }

        // HTTP authentication credential received and match?
        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) && $_SERVER['PHP_AUTH_USER'] == $auth['user'] && $_SERVER['PHP_AUTH_PW'] == $auth['pass']) {
            Cookie::set('__sys_auth__', true);

            return;
        }

        header('WWW-Authenticate: Basic realm="' . utf8_decode('What are you doing here?') . '"');
        header('HTTP/1.0 401 Unauthorized');
        exit('Unauthorized!');
    }

    /**
     * Sets HTTP headers.
     *
     * @return void
     */
    private static function _httpStartup()
    {
        if (defined('STDIN')) {
            return;
        }

        // Send Cache-Control header
        header('Cache-Control: ' . Configuration::get('system', 'cache-control'), true);
    }

    /**
     * Verifies the access to system bug page must be autenticated.
     *
     * @return void
     */
    private static function _systemBugAccess()
    {
        // Has a credential to system bug page?
        $auth = Configuration::get('system', 'bug_authentication');
        if (empty($auth['user']) || empty($auth['pass'])) {
            return;
        }

        // Is authenticated?
        if (Cookie::get('__sys_bug_auth__')) {
            return;
        }

        // Verify the credential
        if (
            !isset($_SERVER['PHP_AUTH_USER'])
            || !isset($_SERVER['PHP_AUTH_PW'])
            || $_SERVER['PHP_AUTH_USER'] != $auth['user']
            || $_SERVER['PHP_AUTH_PW'] != $auth['pass']
        ) {
            header('WWW-Authenticate: Basic realm="' . utf8_decode('What r u doing here?') . '"');
            new Errors(401, 'Unauthorized');
        }

        Cookie::set('__sys_bug_auth__', true);
    }

    /**
     * The system bug pages.
     *
     * @param string $page the command.
     *
     * @return void
     */
    private static function _systemBugPage($page)
    {
        // Check the access
        self::_systemBugAccess();

        $error = new Errors();

        if ($page == '_system_bug_') {
            $error->bugList();

            return;
        }

        if (preg_match('/^[0-9a-z]{8}|all$/', URI::getSegment(1, false))) {
            $error->bugSolved(URI::getSegment(1, false));

            return;
        }

        $error->handler(E_USER_ERROR, 'Page not found', __FILE__, __LINE__, '', 404);
    }

    /**
     * Tests a HTTP error.
     *
     * @return void
     */
    private static function _testError()
    {
        if ($error = URI::getSegment(0)) {
            new Errors((int) $error, 'System error');
        }
    }

    /**
     * Starts system environment.
     */
    public static function initiate($sysconf, $startime = null, $cgiMode = false)
    {
        self::$cgiMode = $cgiMode;
        ini_set('date.timezone', $sysconf['TIMEZONE']);

        // Define the application properties
        self::$startime = is_null($startime) ? microtime(true) : $startime;
        self::systemName($sysconf['SYSTEM_NAME']);
        self::systemVersion($sysconf['SYSTEM_VERSION']);
        self::projectCodeName(isset($sysconf['PROJECT_CODE_NAME']) ? $sysconf['PROJECT_CODE_NAME'] : '');
        self::environment(
            $sysconf['ACTIVE_ENVIRONMENT'],
            isset($sysconf['ENVIRONMENT_ALIAS']) ? $sysconf['ENVIRONMENT_ALIAS'] : [],
            isset($sysconf['ENVIRONMENT_VARIABLE']) ? $sysconf['ENVIRONMENT_VARIABLE'] : ''
        );
        self::charset($sysconf['CHARSET']);

        // Check basic configuration path
        if (!isset($sysconf['ROOT_PATH'])) {
            new Errors(500, 'Web server document root configuration not found.');
        }

        // Define the application paths
        self::path(self::PATH_WEB_ROOT, $sysconf['ROOT_PATH']);
        self::path(self::PATH_LIBRARY, $sysconf['SPRINGY_PATH'] ?? realpath(dirname(__FILE__)));
        self::path(
            self::PATH_PROJECT,
            $sysconf['PROJECT_PATH'] ?? realpath(self::path(self::PATH_LIBRARY) . DS . '..')
        );
        self::path(
            self::PATH_CONF,
            $sysconf['CONFIG_PATH'] ?? realpath(self::path(self::PATH_PROJECT) . DS . 'conf')
        );
        self::path(
            self::PATH_VAR,
            $sysconf['VAR_PATH'] ?? realpath(self::path(self::PATH_PROJECT) . DS . 'var')
        );
        self::path(
            self::PATH_APPLICATION,
            $sysconf['APP_PATH'] ?? realpath(self::path(self::PATH_PROJECT) . DS . 'app')
        );
        self::path(
            self::PATH_CONTROLLER,
            $sysconf['CONTROLER_PATH'] ?? realpath(self::path(self::PATH_APPLICATION) . DS . 'controllers')
        );
        self::path(
            self::PATH_CLASSES,
            $sysconf['CLASS_PATH'] ?? realpath(self::path(self::PATH_APPLICATION) . DS . 'classes')
        );
        self::path(
            self::PATH_MIGRATION,
            $sysconf['MIGRATION_PATH'] ?? realpath(self::path(self::PATH_PROJECT) . DS . 'migration')
        );
        self::path(
            self::PATH_VENDOR,
            $sysconf['VENDOR_PATH'] ?? realpath(self::path(self::PATH_PROJECT) . DS . 'vendor')
        );

        // Pre start check list of application
        self::_httpAuthNeeded();
        self::_httpStartup();
        self::_checkDevAccessDebug();

        // Debug mode?
        ini_set('display_errors', Configuration::get('system', 'debug') ? 1 : 0);

        // System is under maintenance mode?
        if (Configuration::get('system', 'maintenance')) {
            new Errors(503, 'The system is under maintenance');
        }

        // Start the application
        self::_defineController();
        self::_callGlobal();
        self::_callController();
        self::_callSpecialCommands();
    }

    /**
     * Informs if application is running under CGI mode.
     *
     * @return bool
     */
    public static function isCGIMode(): bool
    {
        return self::$cgiMode;
    }

    /**
     * Returns the system runtime until now.
     *
     * @return string
     */
    public static function runTime()
    {
        return number_format(microtime(true) - self::$startime, 6);
    }

    /**
     * The system environment.
     *
     * @param string $env - if defined, set the system environment.
     *
     * @return A string containing the system environment
     */
    public static function environment($env = null, $alias = [], $envar = '')
    {
        if (!is_null($env)) {
            // Define environment by host?
            if (empty($env)) {
                if (!empty($envar)) {
                    $env = getenv($envar);
                }

                $env = empty($env) ? URI::httpHost() : $env;
                if (empty($env)) {
                    $env = 'unknown';
                }

                // Verify if has an alias for host
                if (is_array($alias) && count($alias)) {
                    foreach ($alias as $host => $as) {
                        if (preg_match('/^' . $host . '$/', $env)) {
                            $env = $as;
                            break;
                        }
                    }
                }
            }

            self::$environment = $env;
        }

        return self::$environment;
    }

    /**
     * The system name.
     *
     * @param string $name - if defined, set the system name.
     *
     * @return string A string containing the system name.
     */
    public static function systemName($name = null)
    {
        if (!is_null($name)) {
            self::$name = $name;
        }

        return self::$name;
    }

    /**
     * The system version.
     *
     * @param mixed $major - if defined, set the major part of the system version. Can be an array with all parts.
     * @param mixed $minor - if defined, set the minor part of the system version.
     * @param mixed $build - if defined, set the build part of the system version.
     *
     * @return string A string containing the system version.
     */
    public static function systemVersion($major = null, $minor = null, $build = null)
    {
        if (is_array($major) && is_null($minor) && is_null($build)) {
            return self::systemVersion(isset($major[0]) ? $major[0] : 0, isset($major[1]) ? $major[1] : 0, isset($major[2]) ? $major[2] : 0);
        }

        if (!is_null($major) && !is_null($minor) && !is_null($build)) {
            self::$version = [$major, $minor, $build];
        } elseif (!is_null($major) && !is_null($minor)) {
            self::$version = [$major, $minor];
        } elseif (!is_null($major)) {
            self::$version = [$major];
        }

        return is_array(self::$version) ? implode('.', self::$version) : self::$version;
    }

    /**
     * The project code name.
     *
     * @param string $name - if defined, set the project code name.
     *
     * @return string A string containing the project code name.
     *
     * @see https://en.wikipedia.org/wiki/Code_name#Project_code_name
     */
    public static function projectCodeName($name = null)
    {
        if (!is_null($name)) {
            self::$projName = $name;
        }

        return self::$projName;
    }

    /**
     * The system charset.
     *
     * Default UTF-8
     *
     * @param string $charset - if defined, set the system charset.
     *
     * @return string A string containing the system charset.
     */
    public static function charset($charset = null)
    {
        if (!is_null($charset)) {
            self::$charset = $charset;
            ini_set('default_charset', $charset);
            // Send the content-type and charset header
            header('Content-Type: text/html; charset=' . $charset, true);
        }

        return self::$charset;
    }

    /**
     * A path of the system.
     *
     * @param string $component the component constant.
     * @param string $path      if defined, change the path of the component.
     *
     * @return string A string containing the path of the component.
     */
    public static function path($component, $path = null)
    {
        if (!is_null($path)) {
            self::$paths[$component] = $path;
        }

        return isset(self::$paths[$component]) ? self::$paths[$component] : '';
    }

    /**
     * Adds an error code to the list of ignored errors.
     *
     * @param int|array $error an error code or an array of errors codes.
     *
     * @return void
     */
    public static function addIgnoredError($error)
    {
        if (is_array($error)) {
            foreach ($error as $errno) {
                self::addIgnoredError($errno);
            }

            return;
        }

        if (!in_array($error, self::$ignoredErrors)) {
            self::$ignoredErrors[] = $error;
        }
    }

    /**
     * Removes an error code from the list of ignoded errors.
     *
     * @param int|array $error an error code or an array of errors codes.
     *
     * @return void
     */
    public static function delIgnoredError($error)
    {
        if (is_array($error)) {
            foreach ($error as $errno) {
                self::delIgnoredError($errno);
            }

            return;
        }

        if (in_array($error, self::$ignoredErrors)) {
            $key = array_search($error, self::$ignoredErrors);
            unset(self::$ignoredErrors[$key]);
        }
    }

    /**
     * Gets the array of the ignoded errors.
     *
     * @return array
     */
    public static function getIgnoredError()
    {
        return self::$ignoredErrors;
    }

    /**
     * Calls an error hook function that will be executed just before the framework shows the error message.
     *
     * @param int|string $errno          the number of the error.
     * @param string     $msg            the message for the error.
     * @param int        $errorId        the id of the error.
     * @param mixed      $additionalInfo
     *
     * @return void
     */
    public static function callErrorHook($errno, $msg, $errorId, $additionalInfo)
    {
        if (isset(self::$errorHooks[$errno])) {
            $hook = self::$errorHooks[$errno];
        } elseif (isset(self::$errorHooks['default'])) {
            $hook = self::$errorHooks['default'];
        } elseif (isset(self::$errorHooks['all'])) {
            $hook = self::$errorHooks['all'];
        } elseif (!$hook = Configuration::get('system', 'system_error.hook.' . $errno)) {
            $hook = Configuration::get('system', 'system_error.hook.default');
        }

        if ($hook) {
            if (is_array($hook) && method_exists($hook[0], $hook[1])) {
                $hook[0]->{$hook[1]}($msg, $errno, $errorId, $additionalInfo);
            } elseif (function_exists($hook)) {
                $hook($msg, $errno, $errorId, $additionalInfo);
            }
        }
    }

    /**
     * Sets an error hook function that will be executed just before the framework shows the error message.
     *
     * @param int|string $errno    the number of the error.
     * @param mixed      $funcHook a string with the name of the function or an array with object and function names.
     *
     * @return void
     */
    public static function setErrorHook($errno, $funcHook)
    {
        self::$errorHooks[$errno] = $funcHook;
    }

    /**
     * Gets and/or sets the root controller.
     *
     * @param array $cRoot if defined sets the new root controller.
     *
     * @return array
     */
    public static function controllerRoot($cRoot = null)
    {
        if (!is_null($cRoot)) {
            self::$controller_root = $cRoot;
        }

        return self::$controller_root;
    }

    /**
     * Gets and/or sets the controller namespace.
     *
     * @param string $controller if defined sets the new controller namespace.
     *
     * @return string
     */
    public static function controllerNamespace($controller = null)
    {
        if (!is_null($controller) && file_exists($controller)) {
            $controller = pathinfo($controller);
            $controller = str_replace(self::path(self::PATH_CONTROLLER), '', $controller['dirname']);
            $controller = str_replace(DIRECTORY_SEPARATOR, '/', $controller);
            self::$controller_namespace = trim($controller, '/');
        }

        return self::$controller_namespace;
    }

    /**
     * Assigns a template var used by all templates in system.
     *
     * @param string $name  defines the name of the variable.
     * @param mixed  $value the value to assign to the variable.
     *
     * @return void
     */
    public static function assignTemplateVar($name, $value)
    {
        self::$templateVars[$name] = $value;
    }

    /**
     * Gets a template variable or all is its name is omitted.
     *
     * @param string $var the name of the variable desired.
     *                    If omitted the function will return an array containing all template vars.
     *
     * @return mixed.
     */
    public static function getTemplateVar($var = null)
    {
        if (is_null($var)) {
            return self::$templateVars;
        }

        if (!isset(self::$templateVars[$var])) {
            return;
        }

        return self::$templateVars[$var];
    }

    /**
     * Registers a global function used by all templates is system.
     *
     * @param string $type     defines the type of the function.\n
     *                         Valid values for Smarty driver are "function", "block", "compiler" and "modifier".\n
     *                         For Twig driver always use "function".
     * @param string $name     defines the name of the function.
     * @param mixed  $callback defines the PHP callback.
     *                         For Twig driver it must be a function declaration like this:\n
     *                         function ($value) { return $value; }\n
     *                         For Smarty driver it can be either:\n
     *                         - A string containing the function name;\n
     *                         - An array of the form array($object, $method) with $object
     *                         being a reference to an object and $method being a string containing
     *                         the method-name;\n
     *                         - An array of the form array($class, $method) with $class being the
     *                         class name and $method being a method of the class.
     *
     * Params $cacheable and $cacheAttrs can be omitted in most cases. Used only by Smarty driver.
     *
     * @return void
     */
    public static function registerTemplateFunction($type, $name, $callback, $cacheable = null, $cacheAttrs = null)
    {
        self::$templateFuncs[] = [$type, $name, $callback, $cacheable, $cacheAttrs];
    }

    /**
     * Gets all teplate plugins registered.
     *
     * @return array an array containing all template plugins registered.
     */
    public static function getTemplateFunctions()
    {
        return self::$templateFuncs;
    }

    /**
     * Converts a multidimensional array to the stdClass object.
     *
     * @param mixed $array the array to be converted.
     *
     * @return stdClass
     */
    public static function arrayToObject($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        $object = new \stdClass();
        if (count($array) > 0) {
            foreach ($array as $name => $value) {
                $name = trim($name);
                if (!empty($name)) {
                    $object->$name = self::arrayToObject($value);
                }
            }

            return $object;
        }

        return false;
    }

    /**
     * Converts an object to a multidimensional array.
     *
     * @param mixed $object the object to be converted.
     *
     * @return array
     */
    public static function objectToArray($object)
    {
        if (is_object($object)) {
            $object = get_object_vars($object);
            if (count($object) > 0) {
                foreach ($object as $name => $value) {
                    $name = trim($name);
                    if (!empty($name)) {
                        $object[$name] = self::objectToArray($value);
                    }
                }
            }
        }

        return $object;
    }
}
