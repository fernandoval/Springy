<?php

/**
 * Framework kernel.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Lucas Cardozo <lucas.cardozo@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   3.0.0
 */

namespace Springy;

use Dotenv\Dotenv;

/**
 * Framework kernel class.
 *
 * This is a static class and can not be instantiated by user.
 */
class Kernel
{
    // Framework version
    public const VERSION = '4.6.0-dev';

    // Default controller namespace
    public const DEFAULT_NS = 'App\\Web\\';

    // Path constants
    public const PATH_PROJECT = 'PROJ';             // @deprecated 4.6.0
    public const PATH_CONF = 'CONF';                // @deprecated 4.6.0
    public const PATH_APPLICATION = 'APP';          // @deprecated 4.6.0
    public const PATH_VAR = 'VAR';                  // @deprecated 4.6.0
    public const PATH_CLASSES = 'CLASSES';          // @deprecated 4.6.0
    public const PATH_CONTROLLER = 'CONTROLLER';    // @deprecated 4.6.0
    public const PATH_LIBRARY = 'LIB';              // @deprecated 4.6.0
    public const PATH_WEB_ROOT = 'ROOT';            // @deprecated 4.6.0
    public const PATH_VENDOR = 'VENDOR';            // @deprecated 4.6.0
    public const PATH_MIGRATION = 'MIGRATION';      // @deprecated 4.6.0

    // Determina o root de controladoras
    private static $ctrlRoot = [];
    // The namespace of the controller
    private static $ctrlNameSpace = null;
    // The controller file class name
    private static $controllerName = null;

    // System environment
    private static $environment = '';
    // CGI execution mode
    private static $cgiMode = false;

    // List of ignored errors
    private static $ignoredErrors = [];
    // List of error hook functions
    private static $errorHooks = [];

    // Default template vars
    private static $templateVars = [];
    // Default template functions
    private static $templateFuncs = [];

    /**
     * Bootstrap the application.
     *
     * @return void
     */
    private static function bootstrap(): void
    {
        $btPath = app_path() . DS . 'bootstrap.php';

        if (file_exists($btPath)) {
            require_once $btPath;
        }
    }

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

            foreach ((Configuration::get('uri.routing.routes.' . $namespace) ?: []) as $route => $controller) {
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
    private static function callController(): void
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
     * Executes a framework special command.
     *
     * @return void
     */
    private static function callSpecialCommands(): void
    {
        if (!is_null(self::$controllerName) || self::$cgiMode) {
            return;
        }

        $command = URI::getSegment(0, false);
        $match = match ($command) {
            '__migration__' => defined('STDIN'),
            '_error_' => config_get('system.system_internal_methods.test_error')
                && URI::getSegment(0)
                && ctype_digit(URI::getSegment(0)),
            '_pi_' => config_get('system.system_internal_methods.phpinfo'),
            '_springy_' => config_get('system.system_internal_methods.about'),
            '_system_bug_' => config_get('system.system_internal_methods.system_errors') && self::systemBugAccess(),
            '_system_bug_solved_' => config_get('system.system_internal_methods.system_errors')
                && self::systemBugAccess()
                && preg_match('/(^[0-9a-z]{8}(,[0-9a-z]{8})*|all)$/', URI::getSegment(1, false)),
            default => false,
        };

        $match ? call_user_func(
            match ($command) {
                '__migration__' => fn () => (new Migrator())->run(),
                '_error_' => fn () => new Errors(intval(URI::getSegment(0)), 'System error'),
                '_pi_' => function () {
                    phpinfo();
                    ob_end_flush();
                },
                '_springy_' => fn () => new Core\Copyright(true),
                '_system_bug_' => fn () => (new Errors())->bugList(),
                '_system_bug_solved_' => fn () => (new Errors())->bugSolved(URI::getSegment(1, false)),
            }
        ) : new Errors(404, 'Page not found');
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
        $namespace = explode('/', self::$ctrlNameSpace);
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
    private static function checkDevAccessDebug(): void
    {
        // Has a developer credential?
        $devUser = Configuration::get('system.developer_user');
        $devPass = Configuration::get('system.developer_pass');
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
        $dbaUser = Configuration::get('system.dba_user');
        if (!Configuration::get('system.debug') || !$dbaUser) {
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
                self::$ctrlNameSpace = $config['module'] . '/' . $route;

                return trim($namespace, " \t\0\x0B\\") . '\\';
            }
        }

        self::$ctrlNameSpace = $config['module'];

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

        foreach ((Configuration::get('uri.routing.hosts') ?: []) as $route => $data) {
            $pattern = sprintf('#^%s$#', $route);
            if (preg_match_all($pattern, $host)) {
                self::$ctrlRoot = $data['template'] ?? [];

                return [
                    'module' => $data['module'] ?? '',
                    'namespace' => $data['namespace'] ?? self::DEFAULT_NS,
                    'segments' => $data['segments'] ?? [],
                ];
            }
        }

        return [
            'module' => Configuration::get('uri.routing.module') ?: '',
            'namespace' => Configuration::get('uri.routing.namespace') ?: self::DEFAULT_NS,
            'segments' => Configuration::get('uri.routing.segments') ?: [],
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
    private static function httpAuthNeeded(): void
    {
        $auth = Configuration::get('system.authentication');

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
    private static function httpStartup(): void
    {
        if (defined('STDIN')) {
            return;
        }

        // Send Cache-Control header
        header('Cache-Control: ' . Configuration::get('system.cache-control'), true);
    }

    /**
     * Loads the .env file and merges to Kernel::$sysconf properties.
     *
     * The parameter $sysconf will be removed in v4.7.
     *
     * @param array $sysconf configuration loaded from sysconf.php file.
     *
     * @return void
     */
    private static function loadEnvFile(array $sysconf): void
    {
        $envcache = cache_dir() . DS . '.env.php';
        $envfile = project_path() . DS . '.env';

        // Put legacy sysconf.php array into env
        array_walk($sysconf, fn ($value, $key) => putenv(sprintf('%s=%s', $key, $value)));

        if (
            file_exists($envfile) &&
            (
                !file_exists($envcache) ||
                filemtime($envfile) > filemtime($envcache)
            )
        ) {
            $dotenv = Dotenv::createUnsafeImmutable(project_path());
            $dotenv->safeLoad();
            file_put_contents($envcache, serialize($_ENV));
        } elseif (file_exists($envcache)) {
            $envser = unserialize(file_get_contents($envcache));
            array_walk($envser, fn ($value, $key) => putenv(sprintf('%s=%s', $key, $value)));
        }
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
     * Sets system environment.
     *
     * @return void
     */
    private static function setEnv(): void
    {
        $env = env('SPRINGY_ENVIRONMENT');
        $byHosts = Configuration::get('env_by_host');

        if (is_array($byHosts) && count($byHosts)) {
            $host = URI::getHost() ?: 'localhost';

            // Verify if has an alias for host
            foreach ($byHosts as $host => $alias) {
                if (preg_match('/^' . $host . '$/', $host)) {
                    $env = $alias;
                    break;
                }
            }
        }

        self::$environment = $env;
    }

    /**
     * Verifies the access to system bug page must be autenticated.
     *
     * @return void
     */
    private static function systemBugAccess(): bool
    {
        // Has a credential to system bug page?
        $auth = Configuration::get('system.bug_authentication');

        if (empty($auth['user']) || empty($auth['pass'])) {
            return true;
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

        return true;
    }

    /**
     * Starts the application.
     *
     * The initialization parameter $sysconf will be removed in v4.7.
     *
     * @param array $sysconf configuration loaded from sysconf.php file.
     *
     * @return void
     */
    public static function run(array $sysconf)
    {
        self::loadEnvFile($sysconf);

        self::setEnv();

        ini_set('date.timezone', env('TIMEZONE') ?: 'UTC');
        ini_set('default_charset', charset());
        ini_set('display_errors', Configuration::get('system.debug') ? 1 : 0);
        header('Content-Type: text/html; charset=' . charset(), true);

        // Pre start check list of application
        self::httpAuthNeeded();
        self::httpStartup();
        self::checkDevAccessDebug();

        // System is under maintenance mode?
        if (Configuration::get('system.maintenance')) {
            new Errors(503, 'The system is under maintenance');
        }

        // Start the application
        URI::parseURI();
        self::findControllerByNamespace();
        self::bootstrap();
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
    public static function runTime(): string
    {
        return number_format(microtime(true) - SPRINGY_START, 6);
    }

    /**
     * The system environment.
     *
     * @return string|null
     */
    public static function environment(): ?string
    {
        return self::$environment;
    }

    /**
     * Returns environment data.
     *
     * @param string $key
     *
     * @deprecated 4.6.0
     *
     * @uses env()
     *
     * @return mixed
     */
    public static function systemConfGlobal(string $key): mixed
    {
        return env($key, null);
    }

    /**
     * The system name.
     *
     * Warning! This function will be removed in the future.
     *
     * @deprecated 4.6.0
     *
     * @uses app_name()
     *
     * @return string A string containing the system name.
     */
    public static function systemName(): string
    {
        return app_name();
    }

    /**
     * The system version.
     *
     * Warning! This function will be removed in the future.
     *
     * @deprecated 4.6.0
     *
     * @uses app_version()
     *
     * @see https://semver.org
     *
     * @return string A string containing the system version.
     */
    public static function systemVersion(): string
    {
        if (defined('APP_VERSION')) {
            return app_version();
        }

        [$major, $minor, $patch] = is_array(env('SYSTEM_VERSION', null))
            ? env('SYSTEM_VERSION')
            : explode(
                '.',
                (string) env('SYSTEM_VERSION', '0.0.0')
            );

        return implode('.', [$major ?? 0, $minor ?? 0, $patch ?? 0]);
    }

    /**
     * The project code name.
     *
     * @see https://en.wikipedia.org/wiki/Code_name#Project_code_name
     * @deprecated 4.6.0
     *
     * @uses app_codename()
     *
     * @return string A string containing the project code name.
     */
    public static function projectCodeName(): string
    {
        return app_codename();
    }

    /**
     * The system charset.
     *
     * @deprecated 4.6.0
     *
     * @uses env('CHARSET')
     *
     * @return string A string containing the system charset.
     */
    public static function charset(): string
    {
        return env('CHARSET') ?? 'UTF-8';
    }

    /**
     * A path of the system.
     *
     * @param string $component the component constant.
     *
     * @deprecated 4.6.0
     *
     * @return string A string containing the path of the component.
     */
    public static function path(string $component): string
    {
        return match ($component) {
            self::PATH_APPLICATION => app_path(),
            self::PATH_CONF => config_dir(),
            self::PATH_MIGRATION => migration_dir(),
            self::PATH_PROJECT => project_path(),
            self::PATH_VAR => var_dir(),
        };
    }

    /**
     * Adds an error code to the list of ignored errors.
     *
     * @param int|array $error an error code or an array of errors codes.
     *
     * @return void
     */
    public static function addIgnoredError($error): void
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
    public static function delIgnoredError($error): void
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
        } elseif (!$hook = Configuration::get('system.system_error.hook.' . $errno)) {
            $hook = Configuration::get('system.system_error.hook.default');
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
    public static function setErrorHook($errno, $funcHook): void
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
            self::$ctrlRoot = $cRoot;
        }

        return self::$ctrlRoot;
    }

    /**
     * Gets the controller namespace.
     *
     * @return string
     */
    public static function controllerNamespace(): string
    {
        return self::$ctrlNameSpace ?? '';
    }

    /**
     * Assigns a template var used by all templates in system.
     *
     * @param string $name  defines the name of the variable.
     * @param mixed  $value the value to assign to the variable.
     *
     * @return void
     */
    public static function assignTemplateVar($name, $value): void
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
