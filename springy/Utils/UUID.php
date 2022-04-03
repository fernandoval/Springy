<?php

/**
 * Class library for generate Universally Unique Identifiers (UUID) RFC 4211 compatible.
 *
 * @copyright 2018 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version	  0.1.5
 */

namespace Springy\Utils;

/**
 * Class library for generate Universally Unique Identifiers (UUID) RFC 4211 compatible.
 *
 * Esta classe foi construída a partir da classe desenvolvida por Andrew Moore em comentário
 * na documentação da função uniqid() do PHP.
 *
 * A classe original pode ser obtida no seguinte endereço:
 * http://php.net/manual/en/function.uniqid.php
 */
class UUID
{
    /**
     * Returns a random UUID.
     *
     * @return string
     */
    public static function random()
    {
        $hash = uniqid(md5(rand()));

        return substr($hash, 0, 8) . '-' .
            substr($hash, 8, 4) . '-' .
            substr($hash, 12, 4) . '-' .
            substr($hash, 16, 4) . '-' .
            substr($hash, 20, 12);
    }

    /**
     * Generates a Version 3 UUID.
     *
     * The V3 require a namespace (another valid UUID) and a value (the name).
     * Given the same namespace and name, the output is always the same.
     *
     * @param string $namespace
     * @param string $name
     *
     * @return string|bool The UUID or false if $namespace is not a valid UUID.
     */
    public static function v3($namespace, $name)
    {
        if (!self::isValid($namespace)) {
            return false;
        }

        // Get hexadecimal components of namespace
        $nhex = str_replace(['-', '{', '}'], '', $namespace);
        // Binary Value
        $nstr = '';

        // Convert Namespace UUID to bits
        for ($i = 0; $i < strlen($nhex); $i += 2) {
            $nstr .= chr(hexdec($nhex[$i] . $nhex[$i + 1]));
        }

        // Calculate hash value
        $hash = md5($nstr . $name);

        return sprintf(
            '%08s-%04s-%04x-%04x-%12s',
            // 32 bits for "time_low"
            substr($hash, 0, 8),

            // 16 bits for "time_mid"
            substr($hash, 8, 4),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 3
            (hexdec(substr($hash, 12, 4)) & 0x0FFF) | 0x3000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            (hexdec(substr($hash, 16, 4)) & 0x3FFF) | 0x8000,

            // 48 bits for "node"
            substr($hash, 20, 12)
        );
    }

    /**
     * Generates a Version 4 UUID.
     *
     * The V4 UUIDs are pseudo-random.
     *
     * @return string
     */
    public static function v4()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),

            // 16 bits for "time_mid"
            mt_rand(0, 0xFFFF),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0FFF) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3FFF) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF)
        );
    }

    /**
     * Generates a Version 5 UUID.
     *
     * The V5 require a namespace (another valid UUID) and a value (the name).
     * Given the same namespace and name, the output is always the same.
     *
     * @param string $namespace
     * @param string $name
     *
     * @return string|bool The UUID or false if $namespace is not a valid UUID.
     */
    public static function v5($namespace, $name)
    {
        if (!self::isValid($namespace)) {
            return false;
        }

        // Get hexadecimal components of namespace
        $nhex = str_replace(['-', '{', '}'], '', $namespace);
        // Binary Value
        $nstr = '';

        // Convert Namespace UUID to bits
        for ($i = 0; $i < strlen($nhex); $i += 2) {
            $nstr .= chr(hexdec($nhex[$i] . $nhex[$i + 1]));
        }

        // Calculate hash value
        $hash = sha1($nstr . $name);

        return sprintf(
            '%08s-%04s-%04x-%04x-%12s',
            // 32 bits for "time_low"
            substr($hash, 0, 8),

            // 16 bits for "time_mid"
            substr($hash, 8, 4),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 5
            (hexdec(substr($hash, 12, 4)) & 0x0FFF) | 0x5000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            (hexdec(substr($hash, 16, 4)) & 0x3FFF) | 0x8000,

            // 48 bits for "node"
            substr($hash, 20, 12)
        );
    }

    /**
     * Checks whether the UUID is valid.
     *
     * The UUID must be inside brakets.
     *
     * @param string $uuid
     *
     * @return bool
     */
    public static function isValid($uuid)
    {
        return preg_match(
            '/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i',
            $uuid
        ) === 1;
    }
}
