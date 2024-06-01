<?php

/**
 * Classe statica para tratamento JSON.
 *
 * phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 *
 * @copyright 2009 Lucas Cardozo
 * @author    Lucas Cardozo <lucas.cardozo@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   1.2.3
 */

namespace Springy\Utils;

class JSON_Static
{
    private static $defaultVars = [];

    /**
     * Adds a variable value.
     *
     * @param string $name
     * @param mixed $value
     *
     * @return void
     */
    public static function addDefaultVar(string $name, mixed $value): void
    {
        self::$defaultVars[$name] = $value;
    }

    /**
     * Get all registered vars.
     */
    public static function getDefaultVars(): array
    {
        return self::$defaultVars;
    }

    /**
     * Gets a defined variable.
     *
     * @param string $name
     *
     * @return mixed
     */
    public static function getDefaultVar(string $name, mixed $default = null): mixed
    {
        return self::$defaultVars[$name] ?? $default;
    }
}
