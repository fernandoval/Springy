<?php

/**
 * Application configuration handler.
 *
 * @copyright 2007-2018 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Allan Marques <allan.marques@ymail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   3.2.0
 */

namespace Springy;

use Springy\Exceptions\SpringyException;

/**
 * Application configuration handler.
 *
 * Esta classe é estática e invocada automaticamente pelo framework.
 */
class Configuration
{
    /** @var array the configuration data */
    private static $confs = [];

    public const LC_DB = 'db';
    public const LC_MAIL = 'mail';
    public const LC_SYSTEM = 'system';
    public const LC_TEMPLATE = 'template';
    public const LC_URI = 'uri';

    /**
     * Gets the content of a configuration key.
     *
     * @param string $local   name of the configuration set.
     * @param string $var     configuration key.
     * @param mixed  $default default value if configuration not exists.
     *
     * @return mixed the value of configuration key or $default if not defined.
     */
    public static function get(string $local, ?string $var = null, mixed $default = null)
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

        return Utils\ArrayUtils::newInstance()->dottedGet(self::$confs[$local], $var, $default);
    }

    /**
     * Changes the value of a configuration key.
     *
     * This change is temporary and will exist only during application execution.
     * No changes will be made to the configuration files.
     *
     * @param string $local name of the configuration set.
     * @param mixed  $var   configuration key.
     * @param mixed  $valus new value for configuration.
     *
     * @return void
     */
    public static function set(string $local, mixed $var, mixed $value = null)
    {
        if (is_null($value)) {
            $value = $var;
            $var = '';
            $firstSegment = substr($local, 0, strpos($local, '.'));

            if ($firstSegment) {
                $var = substr($local, strpos($local, '.') + 1);
                $local = $firstSegment;
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
    private static function loadJSON($file, $local)
    {
        if (!file_exists($file . '.json')) {
            return;
        }

        $str = file_get_contents($file . '.json');

        if (!$str) {
            new SpringyException('Can not open the configuration file ' . $file . '.json');
        }

        $conf = json_decode($str, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            new SpringyException('Parse error at ' . $file . '.json: ' . json_last_error_msg());
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
    private static function loadScript($file, $local)
    {
        $oldName = $file . '.conf.php';
        $newName = $file . '.php';
        $fileName = file_exists($newName) ? $newName : $oldName;

        if (!file_exists($fileName)) {
            return;
        }

        $host = URI::getHost();
        $content = require_once $fileName;
        $conf = $conf ?? (is_array($content) ? $content : []);

        self::$confs[$local] = array_replace_recursive(self::$confs[$local], $conf);

        // Overwrite the configuration for a specific host
        if (!$host || !isset($over_conf) || !isset($over_conf[$host])) {
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
    private static function loadAll($file, $local)
    {
        $host = URI::getHost();
        self::loadScript($file, $local);
        self::loadJSON($file, $local);

        // Overwrite the configuration for a specific host, if exists
        if (!$host) {
            return;
        }

        self::loadScript($file . '-' . $host, $local);
        self::loadJSON($file . '-' . $host, $local);
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
        $environment = Kernel::environment();
        self::$confs[$local] = [];

        // Load the default configuration file
        self::loadAll(config_dir() . DS . $local . '.default', $local);
        self::loadAll(config_dir() . DS . $local, $local);

        // Load the configuration file for the current environment
        self::loadAll(config_dir() . DS . $environment . DS . $local, $local);

        // Check if configuration was loaded
        if (empty(self::$confs[$local])) {
            new SpringyException(
                'Settings for "' . $local . '" not found in the environment "' . $environment . '".'
            );
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
        $fileName = config_dir() . DS . Kernel::environment() . DS . $local . '.json';

        if (!file_put_contents($fileName, json_encode(self::$confs[$local]))) {
            new SpringyException('Can not write to ' . $fileName);
        }
    }
}
