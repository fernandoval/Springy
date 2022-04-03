<?php

/**
 * Helper file - Functions and constants.
 *
 * @copyright  2014 Fernando Val
 * @author     Allan Marques <allan.marques@ymail.com>
 * @author     Fernando Val <fernando.val@gmail.com>
 *
 * @version    4.2.0
 *
 * Let's make the developer happier and more productive.
 */

/// Definig the constantes
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

/**
 *  @brief Get shared container application instance.
 *
 *  Returns the shared instance of the application container
 *  or a registered service with the name passed by parameter.
 *
 *  @param string $service Name of the service (optional).
 *
 *  @return class Springy\Core\Application
 */
function app($service = null)
{
    if ($service) {
        return app()->resolve($service);
    }

    return Springy\Core\Application::sharedInstance();
}

/**
 * Gets a key into the array using dotted notation.
 *
 * @param array  $array
 * @param string $key
 * @param mixed  $default
 *
 * @return mixed
 */
function array_dotted_get(array $array, string $key, $default = null)
{
    return Springy\Utils\ArrayUtils::newInstance()->dottedGet($array, $key, $default);
}

/**
 * Returns a URL string using URI::buildUrl() function.
 *
 * @param array  $segments
 * @param array  $query
 * @param string $host
 * @param bool   $addIgnoredSgms
 *
 * @return string
 */
function build_url(
    array $segments = [],
    array $query = [],
    string $host = 'dynamic',
    bool $addIgnoredSgms = true
): string {
    return Springy\URI::buildURL($segments, $query, false, $host, $addIgnoredSgms);
}

/**
 *  @brief An alias for Springy\Configuration::get() method.
 *
 *  @param string $key the name of the configuration key in dotted notation.
 *
 *  @return mixed the value of the key.
 */
function config_get($key)
{
    return Springy\Configuration::get($key);
}

/**
 *  @brief An alias for Springy\Configuration::set() method.
 *
 *  @param string $key the name of the configuration key in dotted notation.
 *  @param string $val the new value of the configuration key.
 */
function config_set($key, $val)
{
    Springy\Configuration::set($key, $val);
}

/**
 *  @brief Global SYSTEM variable wrapper.
 *
 *  @param string $key.
 *
 *  @return mixed.
 */
function sysconf($key)
{
    return $GLOBALS['SYSTEM'][$key] ?? null;
}

/**
 *  @brief An alias for Springy\Core\Debug::add() method.
 *
 *  @param string $txt the text to be printed in debug.
 *  @param string $name a name to the debut information.
 *  @param bool $highlight a flag to set if information will be highlighted.
 *  @param bool $revert.
 *
 *  @return void
 */
function debug($txt, $name = '', $highlight = true, $revert = true)
{
    Springy\Core\Debug::add($txt, $name, $highlight, $revert);
}

/**
 *  @brief A var_dump and die help function.
 *
 *  @param mixed $var the variable or value to be sent to standard output.
 *  @param bool $die a boolen flag to determine if system die after print the value of $var.
 *
 *  @return void
 */
function dd($var, $die = true)
{
    if (Springy\Kernel::isCGIMode()) {
        echo "Status: 200\n\n";
    }

    echo '<pre>';
    var_dump($var);
    echo '</pre>';

    if ($die) {
        exit;
    }
}

/**
 * Helper function to Springy\URI::makeSlug().
 *
 * @param string $text
 * @param string $space
 * @param string $accept
 * @param bool   $lowercase
 *
 * @return string
 */
function make_slug(
    string $text,
    string $separator = '-',
    string $accept = '',
    bool $lowercase = true
): string {
    return Springy\URI::makeSlug($text, $separator, $accept, $lowercase);
}

/**
 *  @brief Minify a CSS or JS file.
 *
 *  @note Is recommend the use of the Minify class by Matthias Mullie.
 *      https://github.com/matthiasmullie/minify
 *
 *  @param string $name the source file name.
 *  @param string $destiny the destination file name.
 *
 *  @return void
 */
function minify($source, $destiny)
{
    $fileType = (substr($source, -4) == '.css' ? 'css' : (substr($source, -3) == '.js' ? 'js' : 'off'));

    // Check the destination directory exists or create if not
    $path = pathinfo($destiny, PATHINFO_DIRNAME);
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }

    $buffer = file_get_contents($source);

    if ($buffer == false) {
        return false;
    }

    if (class_exists('MatthiasMullie\Minify\Minify')) {
        // Use Matthias Mullie's Minify class.
        switch ($fileType) {
            case 'css':
                $minifier = new MatthiasMullie\Minify\CSS($buffer);
                $buffer = $minifier->minify();
                break;
            case 'js':
                $minifier = new MatthiasMullie\Minify\JS($buffer);
                $buffer = $minifier->minify();
                break;
        }
    } elseif ($fileType == 'css') {
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
        $buffer = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '     '], '', $buffer);
        $buffer = preg_replace(['(( )+{)', '({( )+)'], '{', $buffer);
        $buffer = preg_replace(['(( )+})', '(}( )+)', '(;( )*})'], '}', $buffer);
        $buffer = preg_replace(['(;( )+)', '(( )+;)'], ';', $buffer);
    } elseif ($fileType == 'js') {
        $buffer = preg_replace('/((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/', '', $buffer);
        $buffer = str_replace(["\r\n", "\r", "\t", "\n", '  ', '    ', '     '], '', $buffer);
        $buffer = preg_replace(['(( )+\))', '(\)( )+)'], ')', $buffer);
    }

    $return = file_put_contents($destiny, $buffer);
    if ($return !== false) {
        chmod($destiny, 0664);
    }

    return $return;
}

/**
 * Throws a Springy error.
 *
 * @param int    $status
 * @param string $message
 *
 * @return void
 */
function throw_error($status = 500, $message = 'Internal Server Error'): void
{
    new Springy\Errors($status, $message);
}

/**
 *  @brief Return the object (WTF?).
 *
 *  @param mixed $object the object.
 *
 *  @return mixed Return the $object.
 */
function with($object)
{
    return $object;
}

/**
 *  @brief Framework autoload function.
 *
 *  @param string $class the class name.
 *
 *  @return void
 */
function springyAutoload($class)
{
    $aclass = explode('\\', $class);
    if (count($aclass) > 1) {
        if ($aclass[0] == 'Springy') {
            array_shift($aclass);
        }
        $file = implode(DS, $aclass);
    } else {
        $file = $aclass[0];
    }

    if (file_exists(sysconf('SPRINGY_PATH') . DS . $file . '.php')) {
        require_once sysconf('SPRINGY_PATH') . DS . $file . '.php';
    } else {
        // procura na user_classes

        // verifica se a classe utiliza o padrão com namespace (ou classe estática)
        // ex: class Oferta_Comercial_Static == arquivo user_classes/oferta/oferta-comercial.static.php

        preg_match('/^(?<class>[A-Za-z]+)(?<subclass>.*?)(?<type>_Static)?$/', $class, $vars);

        $nameSpace = $class = $vars['class'];

        if (!empty($vars['subclass'])) {
            $class .= '-' . substr($vars['subclass'], 1);
        }

        if (isset($vars['type'])) {
            $class .= '.static';
        } else {
            $class .= '.class';
        }

        // procura a classe nas Libarys
        if (file_exists(sysconf('CLASS_PATH') . DS . $nameSpace . DS . $class . '.php')) {
            require_once sysconf('CLASS_PATH') . DS . $nameSpace . DS . $class . '.php';
        } elseif (file_exists(sysconf('CLASS_PATH') . DS . $class . '.php')) {
            require_once sysconf('CLASS_PATH') . DS . $class . '.php';
        } else {
            $class = $vars['class'];

            if (!empty($vars['subclass'])) {
                $class .= '_' . substr($vars['subclass'], 1);
            }

            if (isset($vars['type'])) {
                $class .= '.static';
            } else {
                $class .= '.class';
            }

            if (file_exists(sysconf('CLASS_PATH') . DS . $nameSpace . DS . $class . '.php')) {
                require_once sysconf('CLASS_PATH') . DS . $nameSpace . DS . $class . '.php';
            } elseif (file_exists(sysconf('CLASS_PATH') . DS . $class . '.php')) {
                require_once sysconf('CLASS_PATH') . DS . $class . '.php';
            }
        }
    }
}

/**
 *  @brief Exception error handler.
 *
 *  @param Error $error the error object.
 *
 *  @return void
 */
function springyExceptionHandler($error)
{
    $errors = new Springy\Errors();
    $errors->handler($error->getCode(), $error->getMessage(), $error->getFile(), $error->getLine(), (method_exists($error, 'getContext') ? $error->getContext() : null));
}

/**
 *  @brief Error handler.
 */
function springyErrorHandler($errno, $errstr, $errfile, $errline, $errContext)
{
    $errors = new Springy\Errors();
    $errors->handler($errno, $errstr, $errfile, $errline, $errContext);
}

/**
 *  @brief Framework Exception class.
 */
class SpringyException extends Exception
{
    /// Contexto do erro
    private $context = null;

    /**
     *  \brief Constructor method.
     */
    public function __construct($code, $message, $file, $line, $context = null)
    {
        parent::__construct($message, $code);
        $this->file = $file;
        $this->line = $line;
        $this->context = $context;
    }

    /**
     *  \brief Return the errot context.
     */
    public function getContext()
    {
        return $this->context;
    }
}

// Define error handlers
error_reporting(E_ALL);
set_exception_handler('springyExceptionHandler');
set_error_handler('springyErrorHandler');
