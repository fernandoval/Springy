<?php
/**
 * Application configuration handler.
 *
 * @copyright ₢ 2007-2018 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Allan Marques <allan.marques@ymail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version    3.0.2.18
 */

namespace Springy;

/**
 * Application configuration handler.
 *
 * Esta classe é estática e invocada automaticamente pelo framework.
 */
class Configuration
{
    /// Configuration array
    private static $confs = [];

    const LC_DB = 'db';
    const LC_MAIL = 'mail';
    const LC_SYSTEM = 'system';
    const LC_TEMPLATE = 'template';
    const LC_URI = 'uri';

    /**
     * Gets the content of a configuration key.
     *
     * @param string $local name of the configuration set.
     * @param string $var   configuration key.
     *
     * @return mixed the value of configuration key or null if not defined.
     */
    public static function get($local, $var = null)
    {
        if (is_null($var)) {
            $firstSegment = substr($local, 0, strpos($local, '.'));

            if ($firstSegment) {
                $var = substr($local, strpos($local, '.') + 1);
                $local = $firstSegment;
            }
        }

        if (!isset(self::$confs[$local])) {
            self::load($local);
        }

        if (!$var) {
            return self::$confs[$local];
        }

        return Utils\ArrayUtils::newInstance()->dottedGet(self::$confs[$local], $var);
    }

    /**
     * Changes the value of a configuration key.
     *
     * This change is temporary and will exist only during application execution.
     * No changes will be made to the configuration files.
     *
     * @param string $local name of the configuration set.
     * @param string $var   configuration key.
     * @param mixed  $valus new value for configuration.
     *
     * @return void
     */
    public static function set($local, $var, $value = null)
    {
        if (is_null($value)) {
            $value = $var;
            $var = '';
            $firstSegment = substr($local, 0, strpos($local, '.'));

            if ($firstSegment) {
                $local = $firstSegment;
                $var = substr($local, strpos($local, '.') + 1);
            }

            if (!$var) {
                self::$confs[$local] = $value;
            }
        }

        Utils\ArrayUtils::newInstance()->dottedSet(self::$confs[$local], $var, $value);
    }

    /**
     * Loads the configuration file in JSON format.
     *
     * @param string $file  configuration file name.
     * @param string $local name of the configuration set.
     *
     * @return void
     */
    private static function _loadJSON($file, $local)
    {
        if (!file_exists($file . '.json')) {
            return;
        }

        if (!$str = file_get_contents($file . '.json')) {
            new Errors(500, 'Can not open the configuration file ' . $file . '.json');
        }

        $conf = json_decode($str, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            new Errors(500, 'Parse error at ' . $file . '.json: ' . json_last_error_msg());
        }

        self::$confs[$local] = array_replace_recursive(self::$confs[$local], $conf);
    }

    /**
     * Loads the configuration file in PHP format.
     *
     * @param string $file  configuration file name.
     * @param string $local name of the configuration set.
     *
     * @return void
     */
    private static function _loadPHP($file, $local)
    {
        if (!file_exists($file . '.conf.php')) {
            return;
        }

        $conf = [];

        require_once $file . '.conf.php';
        self::$confs[$local] = array_replace_recursive(self::$confs[$local], $conf);

        // Overwrite the configuration for a specific host
        if (!isset($over_conf)) {
            return;
        }

        $host = URI::httpHost();

        if (!$host || !isset($over_conf[$host])) {
            return;
        }

        self::$confs[$local] = array_replace_recursive(self::$confs[$local], $over_conf[$host]);
    }

    /**
     * Loads the configuration file.
     *
     * @param string $file  configuration file name.
     * @param string $local name of the configuration set.
     *
     * @return void
     */
    private static function _load($file, $local)
    {
        self::_loadPHP($file, $local);
        self::_loadJSON($file, $local);

        // Overwrite the configuration for a specific host, if exists
        if (!$host = URI::httpHost()) {
            return;
        }

        self::_loadPHP($file . '-' . $host, $local);
        self::_loadJSON($file . '-' . $host, $local);
    }

    /**
     * Loads a configuration set.
     *
     * @param string $local name of the configuration set.
     *
     * @return void
     */
    public static function load($local)
    {
        self::$confs[$local] = [];

        // Load the default configuration file
        self::_load(Kernel::path(Kernel::PATH_CONF) . DS . $local . '.default', $local);

        // Load the configuration file for the current environment
        self::_load(Kernel::path(Kernel::PATH_CONF) . DS . Kernel::environment() . DS . $local, $local);

        // Check if configuration was loaded
        if (empty(self::$confs[$local])) {
            new Errors(500, 'Settings for "' . $local . '" not found in the environment "' . Kernel::environment() . '".');
        }
    }

    /**
     * Saves the configuration set to a JSON file.
     *
     * @param string $local name of the configuration set.
     *
     * @return void
     */
    public static function save($local)
    {
        $fileName = Kernel::path(Kernel::PATH_CONF) . DS . Kernel::environment() . DS . $local . '.json';

        if (!file_put_contents($fileName, json_encode(self::$confs[$local]))) {
            new Errors(500, 'Can not write to ' . $fileName);
        }
    }
}
