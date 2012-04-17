<?php
/**
 *	FVAL PHP Framework for Web Applications\n
 *	Copyright (c) 2007-2009 FVAL Consultoria e Informática Ltda.
 *
 *	\warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\version 1.0.0
 *
 *	\brief Classe para tratamento de strings em formato UTF-8
 */

class Strings_UTF8 {

	/**
	 *	\brief Troca caracteres acentuados por não acentuado
	 */
	public static function remove_accented_chars($txt) {
		$txt = mb_ereg_replace('[áàâãåäªÁÀÂÄÃ]', 'a', $txt);
		$txt = mb_ereg_replace('[éèêëÉÈÊË]', 'e', $txt);
		$txt = mb_ereg_replace('[íìîïÍÌÎÏ]', 'i', $txt);
		$txt = mb_ereg_replace('[óòôõöºÓÒÔÕÖ]', 'o', $txt);
		$txt = mb_ereg_replace('[úùûüÚÙÛÜ]', 'u', $txt);
		$txt = mb_ereg_replace('[ñÑ]', 'n', $txt);
		$txt = mb_ereg_replace('[çÇ]', 'c', $txt);
		$txt = mb_ereg_replace('[ÿ]', 'y', $txt);
		$txt = mb_ereg_replace('[¹]', '1', $txt);
		$txt = mb_ereg_replace('[²]', '2', $txt);
		$txt = mb_ereg_replace('[³]', '3', $txt);

		return $txt;
	}
}
?>