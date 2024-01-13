<?php

/**
 * Framework kernel.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Lucas Cardozo <lucas.cardozo@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   2.9.0
 */

namespace Springy;

use Springy\Exceptions\SpringyException;

/**
 * Framework kernel class.
 *
 * This is a static class and can not be instantiated by user.
 */
class Kernel
{
    // Framework version
    public const VERSION = '4.5.0';

    // Default controller namespace
    public const DEFAULT_NS = 'App\\Web\\';

    // Path constants
    public const PATH_PROJECT = 'PROJ';
    public const PATH_CONF = 'CONF';
    public const PATH_APPLICATION = 'APP';
    public const PATH_VAR = 'VAR';
    public const PATH_CLASSES = 'CLASSES';
    public const PATH_CONTROLLER = 'CONTROLLER';
    public const PATH_LIBRARY = 'LIB';
    public const PATH_ROOT = 'ROOT';
    public const PATH_WEB_ROOT = 'ROOT';
    public const PATH_VENDOR = 'VENDOR';
    public const PATH_MIGRATION = 'MIGRATION';
    // Path constants to back compatibility
    // @deprecated 4.5.0
    public const PATH_CONFIGURATION = self::PATH_CONF;
    public const PATH_SYSTEM = self::PATH_APPLICATION;
    public const PATH_CLASS = self::PATH_CLASSES;

    /**
     * Global system configuration
     *
     * @var array
     */
    private static array $sysconf;

    /// Start time
    private static $startime = null;
    /// Determina o root de controladoras
    private static $controller_root = [];
    /// Caminho do namespace do controller
    private static $controller_namespace = null;
    /** @var string|null The controller file path name */
    private static $controllerFile = null;
    /// The controller file class name
    private static $controllerName = null;

    /// System environment
    private static $environment = '';
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
     * Builds controller name seeking routes configuration.
     *
     * @param string $namespace
     * @param array  $arguments
     * @param bool   $routing
     *
     * @return string
     */
    private static function buildControllerName(string $namespace, array $arguments, bool $routing): string
    {
        if ($routing) {
            $path = implode('/', $arguments);

            foreach ((Configuration::get('uri', 'routing.routes.' . $namespace) ?: []) as $route => $controller) {
                if (preg_match('#' . $route . '#', $path)) {
                    return $namespace . $controller;
                }
            }
        }

        return $namespace . self::normalizeNamePath($arguments);
    }

    /**
     * Calls the application controller if defined.
     *
     * @return void
     */
    private static function callController()
    {
        // Has a controller defined?
        if (is_null(self::$controllerName)) {
            return;
        }

        // Invokes de controller
        self::callControllerMethod(new self::$controllerName());
        // Print out the debug
        Core\Debug::printOut();
    }

    /**
     * Call the right method in the controller.
     *
     * @param object $controller the controller.
     *
     * @return void
     */
    private static function callControllerMethod($controller): void
    {
        $uriSeg = URI::getSegment(0, true);
        $methods = [
            '__invoke',
            '_default',
        ];

        if ($uriSeg) {
            array_unshift($methods, str_replace('-', '_', $uriSeg));
            array_unshift($methods, str_replace('-', '', ucwords($uriSeg, '-_')));
        }

        foreach ($methods as $method) {
            if (is_callable([$controller, $method])) {
                call_user_func([$controller, $method]);

                return;
            }
        }
    }

    /**
     * Starts the global pre-controller hook.
     *
     * @return void
     */
    private static function callGlobal()
    {
        // Bootstrap
        $btPath = self::path(self::PATH_APPLICATION) . DS . 'bootstrap.php';

        // The global pre-controller exists?
        if (file_exists($btPath)) {
            require_once $btPath;

            return;
        }

        // Global pre-controller file path name
        $globalPath = self::path(self::PATH_CONTROLLER) . DS . '_global.php';

        // The global pre-controller exists?
        if (!file_exists($globalPath)) {
            return;
        }

        $controllerName = 'Global_Controller';
        require_once $globalPath;

        if (class_exists($controllerName)) {
            new $controllerName();
        }
    }

    /**
     * Executes a framework special command.
     *
     * @return void
     */
    private static function callSpecialCommands()
    {
        if (!is_null(self::$controllerName) || self::$cgiMode) {
            return;
        }

        $about = function () {
            if (!config_get('system.system_internal_methods.about')) {
                return false;
            }

            new Core\Copyright(true);

            return true;
        };
        $sysbug = function ($command) {
            if (!config_get('system.system_internal_methods.system_errors')) {
                return false;
            }

            self::systemBugPage($command);

            return true;
        };
        $methods = [
            '_' => $about,
            '_springy_' => $about,
            '_pi_' => function () {
                if (!config_get('system.system_internal_methods.phpinfo')) {
                    return false;
                }

                phpinfo();
                ob_end_flush();

                return true;
            },
            '_error_' => function () {
                if (!config_get('system.system_internal_methods.test_error')) {
                    return false;
                }

                self::testError();

                return false;
            },
            '__migration__' => function () {
                // Cli mode only
                if (!defined('STDIN')) {
                    echo 'This script can be executed only in CLI mode.';

                    exit(998);
                }

                $controller = new Migrator();
                $controller->run();

                return true;
            },
            '_system_bug_' => $sysbug,
            '_system_bug_solved_' => $sysbug,
        ];

        // The command
        $command = URI::getSegment(0, false);

        if (($methods[$command] ?? false) && call_user_func($methods[$command], $command)) {
            return;
        }

        new Errors(404, 'Page not found');
    }

    /**
     * Tryes to load a full qualified name controller class.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return bool
     */
    private static function checkController(string $name, array $arguments): bool
    {
        if (!class_exists($name)) {
            return false;
        }

        self::$controllerName = $name;
        $namespace = explode('/', self::$controller_namespace);
        array_shift($namespace);
        URI::setCurrentPage(count($namespace) + count($arguments) - 1);
        URI::setClassController(URI::currentPage());

        return true;
    }

    /**
     * Checks if has a developer accessing for debug.
     *
     * @return void
     */
    private static function checkDevAccessDebug()
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
    private static function defineController(): void
    {
        // Get controller file name
        self::$controllerFile = URI::parseURI();

        if (is_null(self::$controllerFile) || !file_exists(self::$controllerFile)) {
            self::findControllerByNamespace();

            return;
        }

        // Validate the URI and load the controller.
        URI::validateURI();
        self::defineControllerName();
    }

    /**
     * Defines controller class name.
     *
     * @return void
     */
    private static function defineControllerName(): void
    {
        // Load the controller file
        require_once self::$controllerFile;

        $class = URI::getControllerClass();
        $name = str_replace('-', '', ucwords($class, '-'));
        $classNames = [
            'App\\Controller\\' . $name,
            $name . 'Controller', // @deprecated v4.5.0
            str_replace('-', '_', URI::getControllerClass()) . '_Controller', // @deprecated v4.5.0
        ];

        foreach ($classNames as $className) {
            if (class_exists($className)) {
                self::$controllerName = $className;

                return;
            }
        }
    }

    /**
     * Tries to find a web controller from the URI segments using new namespace
     * qualifying PSR-4.
     *
     * @return bool
     */
    private static function findControllerByNamespace(): void
    {
        $arguments = array_filter(URI::getAllSegments());
        $namespace = self::getNamespace($arguments);

        if (self::hasController($namespace, $arguments, false)) {
            return;
        }

        self::hasController($namespace, $arguments, true);
    }

    /**
     * Gets the controller namespace.
     *
     * @param array $segments
     *
     * @return string
     */
    private static function getNamespace(array &$segments): string
    {
        $config = self::getRouteConfiguration();
        $uri = '/' . implode('/', $segments);
        $matches = [];

        foreach ($config['segments'] as $route => $namespace) {
            $pattern = sprintf('#^\/%s(\/(.+))?$#', $route);

            if (preg_match_all($pattern, $uri, $matches, PREG_PATTERN_ORDER)) {
                $segments = explode('/', trim($matches[1][0], '/'));
                self::$controller_namespace = $config['module'] . '/' . $route;

                return trim($namespace, " \t\0\x0B\\") . '\\';
            }
        }

        self::$controller_namespace = $config['module'];

        return trim($config['namespace'] ?? self::DEFAULT_NS, " \t\0\x0B\\") . '\\';
    }

    /**
     * Gets the configuration array for routing.
     *
     * @return array
     */
    private static function getRouteConfiguration(): array
    {
        $host = URI::getHost();

        foreach ((Configuration::get('uri', 'routing.hosts') ?: []) as $route => $data) {
            $pattern = sprintf('#^%s$#', $route);
            if (preg_match_all($pattern, $host)) {
                self::$controller_root = $data['template'] ?? [];

                return [
                    'module' => $data['module'] ?? '',
                    'namespace' => $data['namespace'] ?? self::DEFAULT_NS,
                    'segments' => $data['segments'] ?? [],
                ];
            }
        }

        return [
            'module' => Configuration::get('uri', 'routing.module') ?: '',
            'namespace' => Configuration::get('uri', 'routing.namespace') ?: self::DEFAULT_NS,
            'segments' => Configuration::get('uri', 'routing.segments') ?: [],
        ];
    }

    /**
     * Looking for the controller walking through the arguments.
     *
     * @param string $namespace
     * @param array  $arguments
     * @param bool   $routing
     *
     * @return bool
     */
    private static function hasController(string $namespace, array $arguments, bool $routing): bool
    {
        do {
            if (
                // Adds and finds an Index controller in current $arguments path
                self::checkController(
                    $namespace . self::normalizeNamePath(array_merge($arguments, ['Index'])),
                    $arguments
                )
                ||
                // Removes Index and finds the full qualified name controller
                (
                    count($arguments)
                    && self::checkController(
                        self::buildControllerName($namespace, $arguments, $routing),
                        $arguments
                    )
                )
            ) {
                return true;
            }

            array_pop($arguments);
        } while (count($arguments));

        return false;
    }

    /**
     * Verifies if HTTP authentication is required.
     *
     * @return void
     */
    private static function httpAuthNeeded()
    {
        $auth = Configuration::get('system', 'authentication');

        // HTTP authentication credential not defined?
        if (
            defined('STDIN') ||
            !is_array($auth) ||
            !isset($auth['user']) ||
            !isset($auth['pass']) ||
            Cookie::get('__sys_auth__')
        ) {
            return;
        }

        // HTTP authentication credential received and match?
        if (
            isset($_SERVER['PHP_AUTH_USER']) &&
            isset($_SERVER['PHP_AUTH_PW']) &&
            $_SERVER['PHP_AUTH_USER'] == $auth['user'] &&
            $_SERVER['PHP_AUTH_PW'] == $auth['pass']
        ) {
            Cookie::set('__sys_auth__', true);

            return;
        }

        header('WWW-Authenticate: Basic realm="What are you doing here?"');
        header('HTTP/1.0 401 Unauthorized');
        exit('Unauthorized!');
    }

    /**
     * Sets HTTP headers.
     *
     * @return void
     */
    private static function httpStartup()
    {
        if (defined('STDIN')) {
            return;
        }

        // Send Cache-Control header
        header('Cache-Control: ' . Configuration::get('system', 'cache-control'), true);
    }

    /**
     * Normalizes the array of segments to a class namespace.
     *
     * @param array $segments
     *
     * @return string
     */
    private static function normalizeNamePath(array $segments): string
    {
        $normalized = [];

        foreach ($segments as $value) {
            $normalized[] = studly_caps($value);
        }

        return implode('\\', $normalized);
    }

    /**
     * Verifies the access to system bug page must be autenticated.
     *
     * @return void
     */
    private static function systemBugAccess()
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
            header('WWW-Authenticate: Basic realm="What r u doing here?"');
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
    private static function systemBugPage($page)
    {
        // Check the access
        self::systemBugAccess();

        $error = new Errors();

        if ($page == '_system_bug_') {
            $error->bugList();

            return;
        }

        if (preg_match('/(^[0-9a-z]{8}(,[0-9a-z]{8})*|all)$/', URI::getSegment(1, false))) {
            $error->bugSolved(URI::getSegment(1, false));

            return;
        }

        $error->process(
            new SpringyException('Page not found', E_USER_ERROR, null, __FILE__, __LINE__),
            404
        );
    }

    /**
     * Tests a HTTP error.
     *
     * @return void
     */
    private static function testError()
    {
        if ($error = URI::getSegment(0)) {
            new Errors((int) $error, 'System error');
        }
    }

    /**
     * Starts system environment.
     */
    public static function run(array $sysconf, float $startime)
    {
        self::$startime = $startime;
        self::$sysconf = $sysconf;

        self::environment(
            $sysconf['ACTIVE_ENVIRONMENT'],
            isset($sysconf['ENVIRONMENT_ALIAS']) ? $sysconf['ENVIRONMENT_ALIAS'] : [],
            isset($sysconf['ENVIRONMENT_VARIABLE']) ? $sysconf['ENVIRONMENT_VARIABLE'] : ''
        );

        ini_set('date.timezone', $sysconf['TIMEZONE']);
        ini_set('default_charset', self::charset());
        ini_set('display_errors', Configuration::get('system', 'debug') ? 1 : 0);
        header('Content-Type: text/html; charset=' . self::charset(), true);

        // Pre start check list of application
        self::httpAuthNeeded();
        self::httpStartup();
        self::checkDevAccessDebug();

        // System is under maintenance mode?
        if (Configuration::get('system', 'maintenance')) {
            new Errors(503, 'The system is under maintenance');
        }

        // Start the application
        self::defineController();
        self::callGlobal();
        self::callController();
        self::callSpecialCommands();
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
     * @return mixed A string containing the system environment
     */
    public static function environment($env = null, $alias = [], $envar = '')
    {
        if (!is_null($env)) {
            // Define environment by host?
            if (empty($env)) {
                if (!empty($envar)) {
                    $env = getenv($envar);
                }

                $env = empty($env) ? URI::getHost() : $env;
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

    public static function systemConfGlobal(string $key): mixed
    {
        return self::$sysconf[$key] ?? null;
    }

    /**
     * The system name.
     *
     * @return string A string containing the system name.
     */
    public static function systemName(): string
    {
        return self::$sysconf['SYSTEM_NAME'] ?? '';
    }

    /**
     * The system version.
     *
     * @see https://semver.org
     *
     * @return string A string containing the system version.
     */
    public static function systemVersion()
    {
        [$major, $minor, $patch] = is_array(self::$sysconf['SYSTEM_VERSION'])
            ? self::$sysconf['SYSTEM_VERSION']
            : explode('.', (string) self::$sysconf['SYSTEM_VERSION']);

        return implode('.', [$major ?? 0, $minor ?? 0, $patch ?? 0]);
    }

    /**
     * The project code name.
     *
     * @see https://en.wikipedia.org/wiki/Code_name#Project_code_name
     *
     * @return string A string containing the project code name.
     */
    public static function projectCodeName(): string
    {
        return self::$sysconf['PROJECT_CODE_NAME'] ?? '';
    }

    /**
     * The system charset.
     *
     * @return string A string containing the system charset.
     */
    public static function charset()
    {
        return self::$sysconf['CHARSET'];
    }

    /**
     * A path of the system.
     *
     * @param string $component the component constant.
     *
     * @return string A string containing the path of the component.
     */
    public static function path(string $component): string
    {
        return match ($component) {
            self::PATH_APPLICATION => self::$sysconf['APP_PATH'] ?? self::path(self::PATH_PROJECT) . DS . 'app',
            self::PATH_CLASSES => self::$sysconf['CLASS_PATH'] ?? self::path(self::PATH_APPLICATION) . DS . 'classes',
            self::PATH_CONF => self::$sysconf['CONFIG_PATH'] ?? self::path(self::PATH_PROJECT) . DS . 'conf',
            self::PATH_CONTROLLER => self::$sysconf['CONTROLER_PATH'] ??
                self::path(self::PATH_APPLICATION) . DS . 'controllers',
            self::PATH_LIBRARY => self::$sysconf['SPRINGY_PATH'] ?? __DIR__,
            self::PATH_MIGRATION => self::$sysconf['MIGRATION_PATH'] ??
                self::path(self::PATH_PROJECT) . DS . 'migration',
            self::PATH_PROJECT => self::$sysconf['PROJECT_PATH'] ?? realpath(__DIR__ . DS . '..'),
            self::PATH_VAR => self::$sysconf['VAR_PATH'] ?? self::path(self::PATH_PROJECT) . DS . 'var',
            self::PATH_VENDOR => self::$sysconf['VENDOR_PATH'] ?? self::path(self::PATH_PROJECT) . DS . 'vendor',
            self::PATH_WEB_ROOT => self::$sysconf['ROOT_PATH'],
        };
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
     * @return object|bool
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
