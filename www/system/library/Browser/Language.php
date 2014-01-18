<?php
/**	\file
 *  FVAL PHP Framework for Web Applications
 *
 *  \copyright Copyright (c) 2007-2014 FVAL Consultoria e Informática Ltda.
 *  \copyright Copyright (c) 2007-2014 Fernando Val
 *  \copyright Copyright (c) 2013 Gabriel Bull, dual licensed under GNU GENERAL PUBLIC LICENSE and MIT License.
 *
 *  \brief		Classe de detecção do idioma do navegador
 *  \warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version	1.0.0
 *  \author		Gabriel Bull
 *  \ingroup	framework
 *  \package	browser
 */

namespace FW\Browser;

/**
 *  \brief		Classe de detecção do idioma do navegador
 *  \copyright	Copyright (c) 2013 Gabriel Bull, dual licensed under GNU GENERAL PUBLIC LICENSE and MIT License.
 *  \author		Gabriel Bull
 *
 *  Esta classe foi baseada no conjunto de classes PHP-Browser de Gabriel Bull.
 *
 *  \link https://github.com/gavroche/php-browser
 */
class Language {
    private static $acceptLanguage;
    private static $languages;

    /**
     *  \brief Detect a user's languages and order them by priority
     *
     *  \return void
     */
    private static function checkLanguages() {
        $acceptLanguage = self::getAcceptLanguage();
        self::$languages = array();

        if (!empty($acceptLanguage)) {
            $httpLanguages = preg_split('/q=([\d\.]*)/', $acceptLanguage, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

            $languages = array();
            $key = 0;
            foreach (array_reverse($httpLanguages) as $value) {
                $value = trim($value, ',; .');
                if (is_numeric($value)) {
                    $key = $value;
                } else {
                    $languages[$key] = explode(',', $value);
                }
            }
            krsort($languages);

            foreach ($languages as $value) {
                self::$languages = array_merge(self::$languages, $value);
            }
        }
    }

    /**
     *  \brief Get the accept language value in use to determine the language.
     *
     *  \return string
     */
    public static function getAcceptLanguage() {
        if (!isset(self::$acceptLanguage)) {
            self::setAcceptLanguage(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : "");
        }

        return self::$acceptLanguage;
    }

    /**
     *  Set the accept language value in use to determine the browser.
     *
     *  \param    string $acceptLanguage
     *  \return   void
     */
    public static function setAcceptLanguage($acceptLanguage) {
        self::$acceptLanguage = $acceptLanguage;
    }

    /**
     *  Get all user's languages
     *
     *  \return   array
     */
    public static function getLanguages() {
        if (!is_array(self::$languages)) {
            self::checkLanguages();
        }

        return self::$languages;
    }

    /**
     *  Set languages.
     *
     *  \param   string $value
     *  \return  void
     */
    public static function setLanguages($value) {
        self::$languages = $value;
    }

    /**
     *  Get a user's language
     *
     *  \return  string
     */
    public static function getLanguage() {
        if (!is_array(self::$languages)) {
            self::checkLanguages();
        }

        return strtolower(substr(reset(self::$languages), 0, 2));
    }

    /**
     *  Get a user's language and locale
     *
     *  \return  string
     */
    public static function getLanguageLocale($separator = '-') {
        if (!is_array(self::$languages)) {
            self::checkLanguages();
        }

        $userLanguage = self::getLanguage();
        foreach (self::$languages as $language) {
            if (strlen($language) === 5 && strpos($language, $userLanguage) === 0) {
                $locale = substr($language, -2);
                break;
            }
        }

        if (!empty($locale)) {
            return $userLanguage . $separator . strtoupper($locale);
        } else {
            return $userLanguage;
        }
    }
}