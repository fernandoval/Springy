<?php
/** \file
 *  Springy.
 *
 *  \brief      Cookie treatment class.
 *  \copyright  ₢ 2007-2016 Fernando Val
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \author     Lucas Cardozo - lucas.cardozo@gmail.com
 *  \version    1.3.1.7
 *  \ingroup    framework
 */
namespace Springy;

/**
 *  \brief Cookie treatment class.
 *
 *  \todo  Implementar correção no método delete.
 *         Foi detectado que o método delete não deleta de fato, é necessário, após expirar a chave, setar seu valor para vazio.
 */
class Cookie
{
    // Reserved session keys
    private static $_reserved = [];

    /**
     *	\brief Delete a cookie.
     */
    public static function delete($key)
    {
        // Change string representation array to key/value array
        $key = self::_scrubKey($key);

        // Make sure the cookie exists
        if (self::exists($key)) {
            // Check for key array
            if (is_array($key)) {
                // Grab key/value pair
                list($k, $v) = each($key);

                // Set string representation
                $key = $k.'['.$v.']';

                // Set expiration time to -1hr (will cause browser deletion)
                setcookie($key, false, time() - 3600);

                // Unset the cookie
                unset($_COOKIE[$k][$v]);
            }
            // Check for cookie array
            elseif (is_array($_COOKIE[$key])) {
                foreach ($_COOKIE[$key] as $k => $v) {
                    // Set string representation
                    $cookie = $key.'['.$k.']';

                    // Set expiration time to -1hr (will cause browser deletion)
                    setcookie($cookie, false, time() - 3600);

                    // Unset the cookie
                    unset($_COOKIE[$key][$k]);
                }
            }
            // Unset single cookie
            else {
                // Set expiration time to -1hr (will cause browser deletion)
                setcookie($key, false, time() - 3600);

                // Unset key
                unset($_COOKIE[$key]);
            }
        }
    }

    /**
     *  \brief Alias for delete() function
     *  \deprecated
     *  \warning Deprecated. Do not use. Will be removed soon.
     *  \see delete.
     */
    public static function del($key)
    {
        self::delete($key);
    }

    /**
     *	\brief See if a cookie key exists.
     */
    public static function exists($key)
    {
        // Change string representation array to key/value array
        $key = self::_scrubKey($key);

        // Check for array
        if (is_array($key)) {
            // Grab key/value pair
            list($k, $v) = each($key);

            // Check for key/value pair and return
            if (isset($_COOKIE[$k][$v])) {
                return true;
            }
        }
        // If key exists, return true
        elseif (isset($_COOKIE[$key])) {
            return true;
        }

        // Key does not exist
        return false;
    }

    /**
     *	\brief Get cookie information.
     */
    public static function get($key)
    {
        // Change string representation array to key/value array
        $key = self::_scrubKey($key);

        // Check for array
        if (is_array($key)) {
            // Grab key/value pair
            list($k, $v) = each($key);

            // Check for key/value pair and return
            if (isset($_COOKIE[$k][$v])) {
                return $_COOKIE[$k][$v];
            }
        }
        // Return single key if it's set
        elseif (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        }

        // Otherwise return null
    }

    /**
     *	\brief Return the cookie array.
     */
    public static function contents()
    {
        return $_COOKIE;
    }

    /**
     *	\brief Set cookie information.
     *
     *  Default expire time (session, 1 week = 604800)
     */
    public static function set($key, $value, $expire = 0, $path = '', $domain = '', $secure = false, $httponly = true)
    {
        // Make sure they aren't trying to set a reserved word
        if (!in_array($key, self::$_reserved)) {
            // If $key is in array format, change it to string representation
            $key = self::_scrubKey($key, true);

            // Store the cookie
            return setcookie($key, $value, ($expire ? time() + $expire : 0), $path, $domain, $secure, $httponly);
        }

        // Otherwise, throw an error
        throw new \Exception('Could not set key -- it is reserved.', 500);
    }

    /**
     *	\brief Converts strings to arrays (or vice versa if toString = true).
     */
    private static function _scrubKey($key, $toString = false)
    {
        // Converting from array to string
        if ($toString) {
            // If $key is in array format, change it to string representation
            if (is_array($key)) {
                // Grab key/value pair
                list($k, $v) = each($key);

                // Set string representation
                $key = $k.'['.$v.']';
            }
        }
        // Converting from string to array
        elseif (!is_array($key)) {
            // is this a string representation of an array?
            if (preg_match('/([\w\d]+)\[([\w\d]+)\]$/i', $key, $matches)) {
                // Store as key/value pair
                $key = [$matches[1] => $matches[2]];
            }
        }

        return $key;
    }
}
