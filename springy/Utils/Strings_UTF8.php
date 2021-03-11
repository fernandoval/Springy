<?php

/**	\file
 *	Springy.
 *
 *	\brief		Classe para tratamento de strings em formato UTF-8.
 *  \copyright	Copyright (c) 2007-2016 Fernando Val
 *  \author		Fernando Val  - fernando.val@gmail.com
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	1.6.10
 *	\ingroup	framework
 */

namespace Springy\Utils;

/**
 *  \brief Classe para tratamento de strings em formato UTF-8.
 *
 *  \note Esta classe extende a classe Strings
 */
class Strings_UTF8 extends Strings
{
    /**
     *	\brief Troca caracteres acentuados por não acentuado.
     */
    public static function removeAccentedChars($txt)
    {
        // $txt = preg_replace('/[ÀÁÂÃÄÅ]/u', 'A', $txt);
        // $txt = preg_replace('/[àáâãäå]/u', 'a', $txt);
        $txt = preg_replace('/[Æ]/u', 'AE', $txt);
        $txt = preg_replace('/[æ]/u', 'ae', $txt);
        // $txt = preg_replace('/[Ç]/u', 'C', $txt);
        // $txt = preg_replace('/[ç¢©]/u', 'c', $txt);
        $txt = preg_replace('/[Ð]/u', 'D', $txt);
        $txt = preg_replace('/[∂ð]/u', 'd', $txt);
        // $txt = preg_replace('/[ÈÉÊË]/u', 'E', $txt);
        // $txt = preg_replace('/[èéêë]/u', 'e', $txt);
        // $txt = preg_replace('/[ÌÍÎÏ]/u', 'I', $txt);
        // $txt = preg_replace('/[ìíîï]/u', 'i', $txt);
        // $txt = preg_replace('/[Ñ]/u', 'N', $txt);
        // $txt = preg_replace('/[ñ]/u', 'n', $txt);
        // $txt = preg_replace('/[ÒÓÔÕÖ]/u', 'O', $txt);
        $txt = preg_replace('/[Ø]/u', 'O', $txt);
        // $txt = preg_replace('/[òóôõö°]/u', 'o', $txt);
        $txt = preg_replace('/[ø°]/u', 'o', $txt);
        $txt = preg_replace('/[Œ]/u', 'OE', $txt);
        $txt = preg_replace('/[œ]/u', 'oe', $txt);
        $txt = preg_replace('/[®]/u', 'r', $txt);
        $txt = preg_replace('/[Šß]/u', 'S', $txt);
        $txt = preg_replace('/[§]/u', 'SS', $txt);
        $txt = preg_replace('/[š]/u', 's', $txt);
        $txt = preg_replace('/[™]/u', 'TM', $txt);
        // $txt = preg_replace('/[ÙÚÛÜ]/u', 'U', $txt);
        // $txt = preg_replace('/[ùúûü]/u', 'u', $txt);
        $txt = preg_replace('/[µ]/u', 'u', $txt);
        $txt = preg_replace('/[Ÿ]/u', 'Y', $txt);
        $txt = preg_replace('/[ýÿ]/u', 'y', $txt);
        $txt = preg_replace('/[Ž]/u', 'Z', $txt);
        $txt = preg_replace('/[ž]/u', 'z', $txt);
        // $txt = preg_replace('/[¹]/u', '1', $txt);
        // $txt = preg_replace('/[²]/u', '2', $txt);
        // $txt = preg_replace('/[³]/u', '3', $txt);
        $txt = preg_replace('/[¥]/u', 'JPY', $txt);
        $txt = preg_replace('/[£]/u', 'GBP', $txt);
        $txt = preg_replace('/[†•¶´¨≠±≤≥∑∏π∫Ω]/u', '', $txt);

        // return $txt;
        return strtr(
            utf8_decode($txt),
            utf8_decode('ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöùúûüýÿ¹²³'),
            'AAAAAACEEEEIIIINOOOOOUUUUYaaaaaaceeeeiiiinooooouuuuyy123'
        );
    }

    /**
     *	/brief Converte uma string UTF-8 para Windows-CP-1252.
     */
    public static function convertToWindowsCP1252($string)
    {
        $chars = ['Ç', 'Ä', '£', 'Ä', 'Å', 'Ç', 'É', 'Ñ', 'Ö', 'Ü', 'á', 'à', 'â', 'ä', 'ã', 'å', 'ç', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 'ñ', 'ó', 'ò', 'ô', 'ö', 'õ', 'ú', 'ù', 'û', 'ü', '†', '°', '¢', '£', '§', '•', '¶', 'ß', '®', '©', '™', '´', '¨', '≠', 'Æ', 'Ø', '∞', '±', '≤', '≥', '¥', 'µ', '∂', '∑', '∏', 'π', '∫', 'ª', 'º', 'Ω', 'æ', 'ø'];
        $cp1252 = [chr(128), chr(146), chr(163), chr(192), chr(193), chr(194), chr(195), chr(196), chr(197), chr(198), chr(199), chr(200), chr(201), chr(202), chr(203), chr(204), chr(205), chr(206), chr(207), chr(208), chr(209), chr(210), chr(211), chr(212), chr(213), chr(214), chr(215), chr(216), chr(217), chr(218), chr(219), chr(220), chr(221), chr(222), chr(223), chr(224), chr(225), chr(226), chr(227), chr(228), chr(229), chr(230), chr(231), chr(232), chr(233), chr(234), chr(235), chr(236), chr(237), chr(238), chr(239), chr(240), chr(241), chr(242), chr(243), chr(244), chr(245), chr(246), chr(247), chr(248), chr(249), chr(250), chr(251), chr(252), chr(253), chr(254), chr(255)];

        return str_replace($chars, $cp1252, $string);
    }
}
