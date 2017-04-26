<?php
/** \file
 *  Springy.
 *
 *  \brief      Framework kernel class.
 *  \copyright  (c) 2007-2016 Fernando Val
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \author     Lucas Cardozo - lucas.cardozo@gmail.com
 *  \version    2.3.2.71
 *  \ingroup    framework
 */

namespace Springy;

/**
 *  \brief Framework kernel class.
 *
 *  \warning This is a static class and can not be instantiated by user.
 */
class Kernel
{
    /// Versão do framework
    const VERSION = '3.6.2';

    /// Kernel constants
    const PATH_CLASS = 'CLASS';
    const PATH_CONFIGURATION = 'CONFIGURATION';
    const PATH_CONTROLLER = 'CONTROLLER';
    const PATH_LIBRARY = 'LIBRARY';
    const PATH_ROOT = 'ROOT';
    const PATH_SYSTEM = 'SYSTEM';
    const PATH_VENDOR = 'VENDOR';

    /// Start time
    private static $startime = null;
    /// Determina o root de controladoras
    private static $controller_root = [];
    /// Caminho do namespace do controller
    private static $controller_namespace = null;

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

    /// List of ignored errors
    private static $ignoredErrors = [];
    /// List of error hook functions
    private static $errorHooks = [];

    /// Default template vars
    private static $templateVars = [];
    /// Default template functions
    private static $templateFuncs = [];

    /**
     *  \brief Start system environment.
     */
    public static function initiate($sysconf, $startime = null)
    {
        ini_set('date.timezone', $sysconf['TIMEZONE']);

        self::$startime = is_null($startime) ? microtime(true) : $startime;
        self::systemName($sysconf['SYSTEM_NAME']);
        self::systemVersion($sysconf['SYSTEM_VERSION']);
        self::projectCodeName(isset($sysconf['PROJECT_CODE_NAME']) ? $sysconf['PROJECT_CODE_NAME'] : '');
        self::charset($sysconf['CHARSET']);
        self::environment(
            $sysconf['ACTIVE_ENVIRONMENT'],
            isset($sysconf['ENVIRONMENT_ALIAS']) ? $sysconf['ENVIRONMENT_ALIAS'] : [],
            isset($sysconf['ENVIRONMENT_VARIABLE']) ? $sysconf['ENVIRONMENT_VARIABLE'] : ''
        );

        self::path(self::PATH_LIBRARY, isset($sysconf['LIBRARY_PATH']) ? $sysconf['LIBRARY_PATH'] : realpath(dirname(__FILE__)));
        self::path(self::PATH_SYSTEM, isset($sysconf['SYSTEM_PATH']) ? $sysconf['SYSTEM_PATH'] : realpath(self::path(self::PATH_LIBRARY).DIRECTORY_SEPARATOR.'..'));
        self::path(self::PATH_ROOT, isset($sysconf['ROOT_PATH']) ? $sysconf['ROOT_PATH'] : realpath(self::path(self::PATH_SYSTEM).DIRECTORY_SEPARATOR.'..'));
        self::path(self::PATH_CONTROLLER, isset($sysconf['CONTROLER_PATH']) ? $sysconf['CONTROLER_PATH'] : realpath(self::path(self::PATH_SYSTEM).DIRECTORY_SEPARATOR.'controllers'));
        self::path(self::PATH_CLASS, isset($sysconf['CLASS_PATH']) ? $sysconf['CLASS_PATH'] : realpath(self::path(self::PATH_SYSTEM).DIRECTORY_SEPARATOR.'classes'));
        self::path(self::PATH_CONFIGURATION, isset($sysconf['CONFIG_PATH']) ? $sysconf['CONFIG_PATH'] : realpath(self::path(self::PATH_SYSTEM).DIRECTORY_SEPARATOR.'conf'));
        self::path(self::PATH_VENDOR, isset($sysconf['3RDPARTY_PATH']) ? $sysconf['3RDPARTY_PATH'] : realpath(self::path(self::PATH_SYSTEM).DIRECTORY_SEPARATOR.'other'));
    }

    /**
     *  \brief Return the system runtime until now.
     */
    public static function runTime()
    {
        return number_format(microtime(true) - self::$startime, 6);
    }

    /**
     *  \brief The system environment.
     *
     *  \param string $env - if defined, set the system environment
     *  \return A string containing the system environment
     */
    public static function environment($env = null, $alias = [], $envar = '')
    {
        if (!is_null($env)) {
            // Define environment by host?
            if (empty($env)) {
                if (!empty($envar)) {
                    $env = getenv($envar);
                }

                $env = empty($env) ? URI::http_host() : $env;
                if (empty($env)) {
                    $env = 'unknown';
                }

                // Verify if has an alias for host
                if (is_array($alias) && count($alias)) {
                    foreach ($alias as $host => $as) {
                        if (preg_match('/^'.$host.'$/', $env)) {
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
     *  \brief The system name.
     *
     *  \param string $name - if defined, set the system name
     *  \return A string containing the system name
     */
    public static function systemName($name = null)
    {
        if (!is_null($name)) {
            self::$name = $name;
        }

        return self::$name;
    }

    /**
     *  \brief The system version.
     *
     *  \param $major - if defined, set the major part of the system version. Can be an array with all parts.
     *  \param $minor - if defined, set the minor part of the system version
     *  \param $build - if defined, set the build part of the system version
     *  \return A string containing the system version
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
     *  \brief The project code name.
     *
     *  \param string $name - if defined, set the project code name.
     *  \return A string containing the project code name.
     *  \see https://en.wikipedia.org/wiki/Code_name#Project_code_name
     */
    public static function projectCodeName($name = null)
    {
        if (!is_null($name)) {
            self::$projName = $name;
        }

        return self::$projName;
    }

    /**
     *  \brief The system charset.
     *
     *  Default UTF-8
     *
     *  \param string $charset - if defined, set the system charset
     *  \return A string containing the system charset
     */
    public static function charset($charset = null)
    {
        if (!is_null($charset)) {
            self::$charset = $charset;
            ini_set('default_charset', $charset);
            // Send the content-type and charset header
            header('Content-Type: text/html; charset='.$charset, true);
            if (phpversion() < '5.6') {
                ini_set('mbstring.internal_encoding', $charset);
            }
        }

        return self::$charset;
    }

    /**
     *  \brief A path of the system.
     *
     *  \param string $component - the component constant
     *  \param string $path - if defined, change the path of the component
     *  \return A string containing the path of the component
     */
    public static function path($component, $path = null)
    {
        if (!is_null($path)) {
            self::$paths[$component] = $path;
        }

        return isset(self::$paths[$component]) ? self::$paths[$component] : '';
    }

    /**
     *  \brief Add a error code to the list of ignored errors.
     *
     *  \param (int|array)$error - a error code os a array of errors code
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
     *  \brief Remove a error code from the list of ignoded errors.
     *
     *  \param (int|array)$error - a error code os a array of errors code
     */
    public static function delIgnoredError($error)
    {
        if (is_array($error)) {
            foreach ($error as $errno) {
                self::delIgnoredError($errno);
            }

            return;
        }

        if (isset(self::$ignoredErrors[$error])) {
            unset(self::$ignoredErrors[$error]);
        }
    }

    /**
     *  \brief Get the array of the ignoded errors.
     */
    public static function getIgnoredError()
    {
        return self::$ignoredErrors;
    }

    /**
     *  \brief Set a error hook function that will be executed just before the framework shows the error message.
     */
    public static function callErrorHook($errno, $msg, $errorId, $additionalInfo)
    {
        if (isset(self::$errorHooks[$errno])) {
            $hook = self::$errorHooks[$errno];
        } elseif (isset(self::$errorHooks['default'])) {
            $hook = self::$errorHooks['default'];
        } elseif (isset(self::$errorHooks['all'])) {
            $hook = self::$errorHooks['all'];
        } elseif (!$hook = Configuration::get('system', 'system_error.hook.'.$errno)) {
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
     *  \brief Set a error hook function that will be executed just before the framework shows the error message.
     */
    public static function setErrorHook($errno, $funcHook)
    {
        self::$errorHooks[$errno] = $funcHook;
    }

    /**
     *  \brief Pega ou seta o root de controladoras.
     *
     *  \param (array)$controller_root - ae definido, altera o root de controladoras
     *  \return Retorna um array contendo o root de controladoras
     */
    public static function controllerRoot($controller_root = null)
    {
        if (!is_null($controller_root)) {
            self::$controller_root = $controller_root;
        }

        return self::$controller_root;
    }

    /**
     *	\brief Define o namespace da controller a ser carregada.
     *
     *	\param string $controller
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
     *  \brief Assign a template var used by all templates in system.
     *  \param string $name defines the name of the variable.
     *  \param mixed $value the value to assign to the variable.
     */
    public static function assignTemplateVar($name, $value)
    {
        self::$templateVars[$name] = $value;
    }

    /**
     *  \brief Get a template variable or all is its name is omitted.
     *  \param string $var the name of the variable desired. If omitted the function will return an array containing all template vars.
     *  \return mixed.
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
     *  \brief Register a global function used by all templates is system.
     *  \param string $type defines the type of the function.\n
     *      Valid values for Smarty driver are "function", "block", "compiler" and "modifier".\n
     *      For Twig driver always use "function".
     *  \param string $name defines the name of the function.
     *  \param mixed $callback defines the PHP callback.
     *      For Twig driver it must be a function declaration like this: function ($value) { return $value; }\n
     *      For Smarty driver it can be either:\n
     *          - A string containing the function name;\n
     *          - An array of the form array($object, $method) with $object being a reference to an object and $method being a string containing the method-name;\n
     *          - An array of the form array($class, $method) with $class being the class name and $method being a method of the class.
     *  \param $cacheable and $cacheAttrs can be omitted in most cases. Used only by Smarty driver.
     */
    public static function registerTemplateFunction($type, $name, $callback, $cacheable = null, $cacheAttrs = null)
    {
        self::$templateFuncs[] = [$type, $name, $callback, $cacheable, $cacheAttrs];
    }

    /**
     *  \brief Get all teplate plugins registered.
     *  \return Return an array containing all template plugins registered.
     */
    public static function getTemplateFunctions()
    {
        return self::$templateFuncs;
    }

    /**
     *	\brief Converte um array multidimensional no objeto stdClass.
     *
     *	\param[in] $array (mixed) array a ser convertido
     *	\return Retorna um objeto stdClasse
     */
    public static function arrayToObject($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        $object = new stdClass();
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
     *	\brief Converte um objeto num array multidimensional.
     *
     *	\param[in] $object (mixed) objeto a ser convertido
     *	\return Retorna um array
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
