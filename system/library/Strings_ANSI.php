<?php
/**
 *	FVAL PHP Framework for Web Applications\n
 *	Copyright (c) 2007-2009 FVAL Consultoria e Informבtica Ltda.
 *
 *	\warning Este arquivo י parte integrante do framework e nדo pode ser omitido
 *
 *	\version 1.0.0
 *
 *	\brief Classe para tratamento de strings em formato ANSI
 */

class Strings_ANSI {

	/**
	 *	\brief Troca caracteres acentuados por nדo acentuado
	 */
	public static function remove_accented_chars($txt) {
		$txt = mb_ereg_replace('[באגדוה×ÁÀÂÄÃ]', 'a', $txt);
		$txt = mb_ereg_replace('[יטךכÉÈÊË]', 'e', $txt);
		$txt = mb_ereg_replace('[םלמןÍÌÎÏ]', 'i', $txt);
		$txt = mb_ereg_replace('[ףעפץצ÷ÓÒÔÕÖ]', 'o', $txt);
		$txt = mb_ereg_replace('[תשûüÚÙÛÜ]', 'u', $txt);
		$txt = mb_ereg_replace('[סÑ]', 'n', $txt);
		$txt = mb_ereg_replace('[חÇ]', 'c', $txt);
		$txt = mb_ereg_replace('[ÿ]', 'y', $txt);
		$txt = mb_ereg_replace('[¹]', '1', $txt);
		$txt = mb_ereg_replace('[²]', '2', $txt);
		$txt = mb_ereg_replace('[³]', '3', $txt);

		return $txt;
	}
}
?>