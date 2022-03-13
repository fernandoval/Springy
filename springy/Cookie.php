<?php

/**
 * Cookie treatment class.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Lucas Cardozo <lucas.cardozo@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   1.3.4
 */

namespace Springy;

/**
 * Cookie treatment class.
 *
 * @todo Implementar correção no método delete.
 *       Foi detectado que o método delete não deleta de fato,
 *       é necessário, após expirar a chave, definir seu valor para vazio.
 */
class Cookie
{
    // Reserved session keys
    private static $_reserved = [];

    /**
     * Deletes a cookie.
     *
     * @param string $key the cookie name.
     *
     * @return void
     */
    public static function delete($key)
    {
        // Change string representation array to key/value array
        $key = self::_scrubKey($key);

        // Make sure the cookie exists
        if (!self::exists($key)) {
            return;
        }

        // Check for key array
        if (is_array($key)) {
            // Set string representation
            $cooKey = key($key);
            $cooVal = current($key);
            $cookie = $cooKey . '[' . $cooVal . ']';

            // Set expiration time to -1 day (will cause browser deletion)
            setcookie($cookie, false, time() - 86400);
            // Unset the cookie
            unset($_COOKIE[$cooKey][$cooVal]);

            return;
        }

        // Check for cookie array
        if (is_array($_COOKIE[$key])) {
            foreach ($_COOKIE[$key] as $cooKey => $cooVal) {
                // Set string representation
                $cookie = $key . '[' . $cooKey . ']';

                // Set expiration time to -1 day (will cause browser deletion)
                setcookie($cookie, false, time() - 86400);
            }

            // Unset the cookie
            unset($_COOKIE[$key]);

            return;
        }

        // Unset single cookie
        // Set expiration time to -1 day (will cause browser deletion)
        setcookie($key, false, time() - 86400);
        // Unset key
        unset($_COOKIE[$key]);
    }

    /**
     * Alias for delete() function.
     *
     * @deprecated
     * @see delete.
     */
    public static function del($key)
    {
        self::delete($key);
    }

    /**
     * Checks if a cookie key exists.
     *
     * @param string $key the cookie name.
     *
     * @return bool
     */
    public static function exists($key)
    {
        // Change string representation array to key/value array
        $key = self::_scrubKey($key);

        // Check for array
        if (is_array($key)) {
            // Grab key/value pair
            $cooKey = key($key);
            $cooVal = current($key);

            return isset($_COOKIE[$cooKey][$cooVal]);
        }

        return isset($_COOKIE[$key]);
    }

    /**
     * Gets a cookie data.
     *
     * @param string $key the cookie name.
     *
     * @return mixed
     */
    public static function get($key)
    {
        // Change string representation array to key/value array
        $key = self::_scrubKey($key);

        // Check for array
        if (is_array($key)) {
            // Grab key/value pair
            $cooKey = key($key);
            $cooVal = current($key);

            return $_COOKIE[$cooKey][$cooVal] ?? null;
        }

        return $_COOKIE[$key] ?? null;
    }

    /**
     * Returns the array with all cookies values.
     *
     * @return array
     */
    public static function contents()
    {
        return $_COOKIE;
    }

    /**
     * Sets a cookie data.
     *
     * @param string $key      the cookie name.
     * @param mixed  $value    value for the cookie.
     * @param int    $expire   the time the cookie expires.
     * @param string $path     the path on the server in which the cookie will be available on.
     * @param string $domain   the (sub)domain that the cookie is available to.
     * @param bool   $secure   indicates that the cookie should only be transmitted over a secure HTTPS connection from the client.
     * @param bool   $httponly when TRUE the cookie will be made accessible only through the HTTP protocol.
     *
     * @return void
     */
    public static function set(
        $key,
        $value,
        $expire = 0,
        $path = '',
        $domain = '',
        $secure = false,
        $httponly = true
    ) {
        // Make sure they aren't trying to set a reserved word
        if (!in_array($key, self::$_reserved)) {
            // If $key is in array format, change it to string representation
            $key = self::_scrubKey($key, true);

            // Store the cookie
            return setcookie(
                $key,
                $value,
                ($expire ? time() + $expire : 0),
                $path,
                $domain,
                $secure,
                $httponly
            );
        }

        // Otherwise, throw an error
        throw new \Exception('Could not set key -- it is reserved.', 500);
    }

    /**
     * Converts strings to arrays (or vice versa if toString = true).
     */
    private static function _scrubKey($key, $toString = false)
    {
        // Converting from array to string
        if ($toString) {
            // If $key is in array format, change it to string representation
            if (is_array($key)) {
                // Grab key/value pair
                $cooKey = key($key);
                $cooVal = current($key);
                // Set string representation
                $key = $cooKey . '[' . $cooVal . ']';
            }

            return $key;
        }

        // Converting from string to array
        if (!is_array($key)) {
            // is this a string representation of an array?
            if (preg_match('/([\w\d]+)\[([\w\d]+)\]$/i', $key, $matches)) {
                // Store as key/value pair
                $key = [$matches[1] => $matches[2]];
            }
        }

        return $key;
    }
}
