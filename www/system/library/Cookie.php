<?php
/** \file
 *  Springy.
 *
 *  \brief      Script da classe para tratamento de cookies.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \author     Fernando Val  - fernando.val@gmail.com
 *  \author     Lucas Cardozo - lucas.cardozo@gmail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    1.2.6
 *  \ingroup    framework
 */
namespace Springy;

/**
 *  \brief Classe para tratamento de cookies.
 *
 *  \todo  Implementar correção no método delete.
 *         Foi detectado que o método delete não deleta de fato, é necessário, após expirar a chave, setar seu valor para vazio.
 */
class Cookie
{
    // [en-us] Reserved session keys
    private static $_reserved = [];

    /**
     *	\brief Delete a cookie.
     */
    public static function delete($key)
    {
        // [en-us] Change string representation array to key/value array
        $key = self::_scrubKey($key);

        // [en-us] Make sure the cookie exists
        if (self::exists($key)) {
            // [en-us] Check for key array
            if (is_array($key)) {
                // [en-us] Grab key/value pair
                list($k, $v) = each($key);

                // [en-us] Set string representation
                $key = $k.'['.$v.']';

                // [en-us] Set expiration time to -1hr (will cause browser deletion)
                setcookie($key, false, time() - 3600);

                // [en-us] Unset the cookie
                unset($_COOKIE[$k][$v]);
            }
            // [en-us] Check for cookie array
            elseif (is_array($_COOKIE[$key])) {
                foreach ($_COOKIE[$key] as $k => $v) {
                    // [en-us] Set string representation
                    $cookie = $key.'['.$k.']';

                    // [en-us] Set expiration time to -1hr (will cause browser deletion)
                    setcookie($cookie, false, time() - 3600);

                    // [en-us] Unset the cookie
                    unset($_COOKIE[$key][$k]);
                }
            }
            // [en-us] Unset single cookie
            else {
                // [en-us] Set expiration time to -1hr (will cause browser deletion)
                setcookie($key, false, time() - 3600);

                // [en-us] Unset key
                unset($_COOKIE[$key]);
            }
        }
    }

    /**
     *  \brief Alias for delete() function
     *  \deprecated
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
        // [en-us] Change string representation array to key/value array
        $key = self::_scrubKey($key);

        // [en-us] Check for array
        if (is_array($key)) {
            // [en-us] Grab key/value pair
            list($k, $v) = each($key);

            // [en-us] Check for key/value pair and return
            if (isset($_COOKIE[$k][$v])) {
                return true;
            }
        }
        // [en-us] If key exists, return true
        elseif (isset($_COOKIE[$key])) {
            return true;
        }

        // [en-us] Key does not exist
        return false;
    }

    /**
     *	\brief Get cookie information.
     */
    public static function get($key)
    {
        // [en-us] Change string representation array to key/value array
        $key = self::_scrubKey($key);

        // [en-us] Check for array
        if (is_array($key)) {
            // [en-us] Grab key/value pair
            list($k, $v) = each($key);

            // [en-us] Check for key/value pair and return
            if (isset($_COOKIE[$k][$v])) {
                return $_COOKIE[$k][$v];
            }
        }
        // [en-us] Return single key if it's set
        elseif (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        }

        // [en-us] Otherwise return null
        else {
            return;
        }
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
        // [en-us] Make sure they aren't trying to set a reserved word
        if (!in_array($key, self::$_reserved)) {
            // [en-us] If $key is in array format, change it to string representation
            $key = self::_scrubKey($key, true);

            // [en-us] Store the cookie
            setcookie($key, $value, ($expire ? time() + $expire : 0), $path, $domain, $secure, $httponly);
        }
        // [en-us] Otherwise, throw an error
        else {
            Error::warning('Could not set key -- it is reserved.', __CLASS__);
        }
    }

    /**
     *	\brief Converts strings to arrays (or vice versa if toString = true).
     */
    private static function _scrubKey($key, $toString = false)
    {
        // [en-us] Converting from array to string
        if ($toString) {
            // [en-us] If $key is in array format, change it to string representation
            if (is_array($key)) {
                // [en-us] Grab key/value pair
                list($k, $v) = each($key);

                // [en-us] Set string representation
                $key = $k.'['.$v.']';
            }
        }
        // [en-us] Converting from string to array
        elseif (!is_array($key)) {
            // [en-us] is this a string representation of an array?
            if (preg_match('/([\w\d]+)\[([\w\d]+)\]$/i', $key, $matches)) {
                // [en-us] Store as key/value pair
                $key = [$matches[1] => $matches[2]];
            }
        }

        return $key;
    }
}
