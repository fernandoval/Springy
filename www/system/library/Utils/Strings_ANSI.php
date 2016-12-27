<?php
/** \file
 *  Springy.
 *
 *  \brief      Classe para tratamento de strings em formato ANSI.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \author     Fernando Val  - fernando.val@gmail.com
 *  \author     Lucas Cardozo - lucas.cardozo@gmail.com
 *  \warning    Este arquivo й parte integrante do framework e nгo pode ser omitido
 *  \version    1.5.9
 *  \ingroup    framework
 */

namespace Springy\Utils;

/**
 *  \brief Classe para tratamento de strings em formato ANSI.
 *
 *  \note Esta classe extende a classe Strings
 */
class Strings_ANSI extends Strings
{
    /**
     *  \brief Troca caracteres acentuados por nгo acentuado.
     */
    public static function removeAccentedChars($txt)
    {
        $txt = preg_replace('/[бавгед™Ѕј¬ƒ√™]/u', 'a', $txt);
        $txt = preg_replace('/[йикл…» Ћ]/u', 'e', $txt);
        $txt = preg_replace('/[нмопЌћќѕ]/u', 'i', $txt);
        $txt = preg_replace('/[утфхцЇ”“‘’÷Ї]/u', 'o', $txt);
        $txt = preg_replace('/[ъщыьЏўџ№µ]/u', 'u', $txt);
        $txt = preg_replace('/[с—]/u', 'n', $txt);
        $txt = preg_replace('/[з«]/u', 'c', $txt);
        $txt = preg_replace('/[–]/u', 'd', $txt);
        $txt = preg_replace('/[КЪ]/u', 's', $txt);
        $txt = preg_replace('/[€•]/u', 'y', $txt);
        $txt = preg_replace('/[ОЮ]/u', 'z', $txt);
        $txt = preg_replace('/[є]/u', '1', $txt);
        $txt = preg_replace('/[≤]/u', '2', $txt);
        $txt = preg_replace('/[≥]/u', '3', $txt);
        $txt = preg_replace('/[∆ж]/u', 'ae', $txt);
        $txt = preg_replace('/[Ўш]/u', '0', $txt);
        $txt = preg_replace('/[Ж∞Ґ£ІХґяЃ©Щі®±]/u', '', $txt);

        return $txt;
    }

    /**
     *  /brief Converte uma string ANSI para Windows-CP-1252.
     */
    public static function convertToWindowsCP1252($string)
    {
        $chars = ['«', 'ƒ', '£', 'ƒ', '≈', '«', '…', '—', '÷', '№', 'б', 'а', 'в', 'д', 'г', 'е', 'з', 'й', 'и', 'к', 'л', 'н', 'м', 'о', 'п', 'с', 'у', 'т', 'ф', 'ц', 'х', 'ъ', 'щ', 'ы', 'ь', 'Ж', '∞', 'Ґ', '£', 'І', 'Х', 'ґ', 'я', 'Ѓ', '©', 'Щ', 'і', '®', '∆', 'Ў', '±', '•', 'µ', '™', 'Ї', 'ж', 'ш'];
        $cp1252 = [chr(128), chr(146), chr(163), chr(192), chr(193), chr(194), chr(195), chr(196), chr(197), chr(198), chr(199), chr(200), chr(201), chr(202), chr(203), chr(204), chr(205), chr(206), chr(207), chr(208), chr(209), chr(210), chr(211), chr(212), chr(213), chr(214), chr(215), chr(216), chr(217), chr(218), chr(219), chr(220), chr(221), chr(222), chr(223), chr(224), chr(225), chr(226), chr(227), chr(228), chr(229), chr(230), chr(231), chr(232), chr(233), chr(234), chr(235), chr(236), chr(238), chr(239), chr(241), chr(244), chr(245), chr(251), chr(252), chr(254), chr(255)];

        return str_replace($chars, $cp1252, $string);
    }
}
