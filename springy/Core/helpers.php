<?php

/**
 * Helper file - Functions and constants.
 *
 * Let's make the developer happier and more productive.
 *
 * @copyright  2014 Fernando Val
 * @author     Allan Marques <allan.marques@ymail.com>
 * @author     Fernando Val <fernando.val@gmail.com>
 *
 * @version    5.0.0
 */

// Definig the constantes
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('LF')) {
    define('LF', "\n");
}
if (!defined('USER_AUTH_DRIVE')) {
    define('USER_AUTH_DRIVE', 'user.auth.driver');
}
if (!defined('USER_AUTH_HASHER')) {
    define('USER_AUTH_HASHER', 'security.hasher');
}
if (!defined('USER_AUTH_IDENTITY')) {
    define('USER_AUTH_IDENTITY', 'user.auth.identity');
}
if (!defined('USER_AUTH_MANAGER')) {
    define('USER_AUTH_MANAGER', 'user.auth.manager');
}

/**
 * Get shared container application instance.
 *
 * Returns the shared instance of the application container
 * or a registered service with the name passed by parameter.
 *
 * @param string|Closure $service Name of the service (optional).
 *                                If null, returnos Springy\Core\Application instance.
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
 * Returns the project code name.
 *
 * @see https://en.wikipedia.org/wiki/Code_name#Project_code_name
 *
 * @return string
 */
function app_codename(): string
{
    return defined('APP_CODE_NAME') ? APP_CODE_NAME : env('PROJECT_CODE_NAME', '');
}

/**
 * Returns the application name.
 *
 * @return string
 */
function app_name(): string
{
    return defined('APP_NAME') ? APP_NAME : env('SYSTEM_NAME', '');
}

/**
 * Returns application path.
 *
 * @return string
 */
function app_path(): string
{
    return defined('APP_PATH') ? APP_PATH : project_path() . DS . 'app';
}

/**
 * Returns the application version.
 *
 * @see https://semver.org
 *
 * @return string
 */
function app_version(): string
{
    return defined('APP_VERSION') ? APP_VERSION : Springy\Kernel::systemVersion();
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
 * Returns var cached files directory path.
 *
 * @return string
 */
function cache_dir(): string
{
    return defined('CACHE_DIR') ? CACHE_DIR : var_dir() . DS . 'cache';
}

/**
 * Returns configuration files path.
 *
 * @return string
 */
function config_dir(): string
{
    return defined('CONFIG_DIR') ? CONFIG_DIR : project_path() . DS . 'conf';
}

/**
 * An alias for Springy\Configuration::get() method.
 *
 * @param string $key     the name of the configuration key in dotted notation.
 * @param mixed  $default default value if configuration not exists.
 *
 * @return mixed the value of the key.
 */
function config_get(string $key, mixed $default = null): mixed
{
    return Springy\Configuration::get($key, null, $default);
}

/**
 * An alias for Springy\Configuration::set() method.
 *
 * @param string $key the name of the configuration key in dotted notation.
 * @param mixed  $val the new value of the configuration key.
 */
function config_set(string $key, mixed $val): void
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
function cookie_get(string $name): mixed
{
    return Springy\Cookie::get($name);
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
 * Returns environment charset.
 *
 * @return string
 */
function charset(): string
{
    return env('CHARSET') ?? 'UTF-8';
}

/**
 * Gets an environment variable.
 *
 * @param string $key
 * @param mixed  $default
 *
 * @return mixed
 */
function env(string $key, $default = null)
{
    $getenv = getenv($key);

    if (!isset($_ENV[$key]) && !isset($_SERVER[$key]) && $getenv === false) {
        return $default;
    }

    $value = $_ENV[$key] ?? $_SERVER[$key] ?? $getenv;
    $vLength = strlen($value);

    if ($vLength > 1 && $value[0] === '"' && $value[$vLength - 1] === '"') {
        return substr($value, 1, -1);
    }

    return $value;
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
 * Gets a string of memory usage representation.
 *
 * @param int $memory
 *
 * @return string
 */
function memory_string(int $memory): string
{
    $unit = [
        'B',
        'KiB',
        'MiB',
        'GiB',
        'TiB',
        'PiB',
    ];

    return round(
        $memory / pow(1024, $idx = floor(log($memory, 1024))),
        2
    ) . ' ' . $unit[$idx];
}

/**
 * Returns directory path for the migration scripts.
 *
 * @return string
 */
function migration_dir(): string
{
    return defined('MIGRATION_DIR') ? MIGRATION_DIR : project_path() . DS . 'migration';
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
 * Returns project root path.
 *
 * @return string
 */
function project_path(): string
{
    return defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 2);
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
 * Global SYSTEM variable wrapper.
 *
 * @param string $key.
 *
 * @deprecated 4.6.0
 *
 * @uses env()
 *
 * @return mixed.
 */
function sysconf($key): mixed
{
    return env($key, null);
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

/**
 * Returns var files directory path.
 *
 * @return string
 */
function var_dir(): string
{
    return defined('VAR_DIR') ? VAR_DIR : project_path() . DS . 'var';
}

/**
 * Returns web root directory path.
 *
 * @return string
 */
function web_root(): string
{
    return defined('WEB_ROOT') ? WEB_ROOT : project_path() . DS . 'public';
}
