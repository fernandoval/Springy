<?php

/**
 * Helper file - Functions and constants.
 *
 * @copyright  2014 Fernando Val
 * @author     Allan Marques <allan.marques@ymail.com>
 * @author     Fernando Val <fernando.val@gmail.com>
 *
 * @version    4.8.0
 *
 * Let's make the developer happier and more productive.
 */

// Definig the constantes
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

/**
 * Get shared container application instance.
 *
 * Returns the shared instance of the application container
 * or a registered service with the name passed by parameter.
 *
 * @param string $service Name of the service (optional).
 *                        If null, returnos Springy\Core\Application instance.
 *
 * @return mixed
 */
function app($service = null)
{
    if ($service) {
        return app()->offsetGet($service);
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
 * An alias for Springy\Configuration::get() method.
 *
 * @param string $key the name of the configuration key in dotted notation.
 *
 * @return mixed the value of the key.
 */
function config_get($key): mixed
{
    return Springy\Configuration::get($key);
}

/**
 * An alias for Springy\Configuration::set() method.
 *
 * @param string $key the name of the configuration key in dotted notation.
 * @param string $val the new value of the configuration key.
 */
function config_set($key, $val)
{
    Springy\Configuration::set($key, $val);
}

/**
 * An alias for Springy\Cookie::get() method.
 *
 * @param string $key the name of the cookie variable.
 *
 * @return mixed the value of the cookie.
 */
function cookie_get($name): mixed
{
    return Springy\Cookie::get($name);
}

/**
 * Global SYSTEM variable wrapper.
 *
 * @param string $key.
 *
 * @return mixed.
 */
function sysconf($key): mixed
{
    // return $GLOBALS['SYSTEM'][$key] ?? null;
    return Springy\Kernel::systemConfGlobal($key);
}

/**
 * An alias for Springy\Core\Debug::add() method.
 *
 * @param string $txt       the text to be printed in debug.
 * @param string $name      a name to the debut information.
 * @param bool   $highlight a flag to set if information will be highlighted.
 * @param bool   $revert.
 *
 * @return void
 */
function debug($txt, $name = '', $highlight = true, $revert = true)
{
    Springy\Core\Debug::add($txt, $name, $highlight, $revert);
}

/**
 * A var_dump and die help function.
 *
 * @param mixed $var the variable or value to be sent to standard output.
 * @param bool  $die a boolen flag to determine if system die after print the value of $var.
 *
 * @return void
 */
function dd($var, $die = true)
{
    // if (Springy\Kernel::isCGIMode()) {
    //     echo "Status: 200\n\n";
    // }

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
 * Makes directory recusrively and grants permission mode avoiding umask.
 *
 * @param string $dirpath
 * @param int    $mode
 *
 * @return bool
 */
function mkdir_recursive(string $dirpath, $mode): bool
{
    $dirpath = rtrim($dirpath, DS);

    if (empty($dirpath)) {
        throw_error(500, 'Empty path.');
    }

    $parts = explode(DS, $dirpath);
    $base = '';

    foreach ($parts as $dir) {
        $base .= $dir;

        if ($dir === '' || $dir === '.' || $dir === '..' || is_dir($base)) {
            $base .= DS;

            continue;
        } elseif (!mkdir($base, $mode)) {
            return false;
        }

        if ($mode != ($mode & ~umask())) {
            chmod($base, $mode);
        }

        $base .= DS;
    }

    return true;
}

/**
 * @brief Minify a CSS or JS file.
 *
 * @note Is recommend the use of the Minify class by Matthias Mullie.
 *       https://github.com/matthiasmullie/minify
 *
 * @param string $name    the source file name.
 * @param string $destiny the destination file name.
 *
 * @return void
 */
function minify($source, $destiny)
{
    $fileType = (substr($source, -4) == '.css' ? 'css' : (substr($source, -3) == '.js' ? 'js' : 'off'));
    $path = pathinfo($destiny, PATHINFO_DIRNAME);

    // Check the destination directory exists or create if not
    mkdir_recursive($path, 0775);

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
 * Returns a string in StudlyCaps format.
 *
 * @param string $value
 *
 * @return string
 */
function studly_caps(string $value): string
{
    $normalized = [];
    $segments = explode('-', $value);
    foreach ($segments as $value) {
        $normalized[] = $value ? ucwords($value, '_') : '-';
    }

    return implode('', $normalized);
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
 * Return the object (WTF?).
 *
 * @param mixed $object the object.
 *
 * @return mixed Return the $object.
 *
 * @deprecated 4.4.0
 */
function with($object)
{
    return $object;
}

/**
 * Framework autoload function.
 *
 * Will be removed in v4.6.0
 *
 * @param string $class the class name.
 *
 * @deprecated 4.5.0
 *
 * @return void
 */
function springyAutoload($class)
{
    // New namespaced application class loader
    $classFile = sysconf('CLASS_PATH') . DS . implode(DS, explode('\\', $class)) . '.php';

    if (file_exists($classFile)) {
        require_once $classFile;

        return;
    }

    // Old loader method
    preg_match('/^(?<class>[A-Za-z]+)(?<subclass>.*?)(?<type>_Static)?$/', $class, $vars);

    $prefix = sysconf('CLASS_PATH') . DS;
    $suffix = isset($vars['type']) ? '.static.php' : '.class.php';
    $nameSpace = $vars['class'];
    $fileNames = [
        $suffix,
        '.php',
    ];

    if (!empty($vars['subclass'])) {
        $subclass = substr($vars['subclass'], 1);
        $fileNames = [
            DS . $nameSpace . '-' . $subclass . $suffix,
            DS . $nameSpace . '_' . $subclass . $suffix,
            $vars['subclass'] . $suffix,
            DS . $nameSpace . '-' . $subclass . '.php',
            DS . $nameSpace . '_' . $subclass . '.php',
            $vars['subclass'] . '.php',
        ];
    }

    foreach ($fileNames as $file) {
        $path = $prefix . $nameSpace . $file;

        if (file_exists($path)) {
            require_once $path;

            return;
        }
    }
}

/**
 * Exception error handler.
 *
 * @param Throwable $error
 *
 * @return void
 */
function springyExceptionHandler(Throwable $error)
{
    (new Springy\Errors())->process($error, 500);
}

/**
 * Error handler.
 */
function springyErrorHandler($errno, $errstr, $errfile, $errline)
{
    (new Springy\Errors())->process(
        new Springy\Exceptions\SpringyException($errstr, $errno, null, $errfile, $errline),
        500
    );
}
