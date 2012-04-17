<?php
/**
 *	FVAL PHP Framework for Web Applications\n
 *	Copyright (c) 2007-2009 FVAL Consultoria e Informática Ltda.
 *
 *	\warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\version 0.3.0
 *
 *	\brief Classe para tratamento de sessão
 */

session_start();

class Session extends Kernel {
 
	/**
	 *	\brief Classe estática não pode ser inicializada
	 */
    private function __construct() {}

	/**
	 *	\brief Informa se a variável de sessão está definida
	 */
	public static function is_set($var) {
		return isset($_SESSION['_ffw_'][$var]);
	}

	/**
	 *	\brief Coloca um valor em variável de sessão
	 */
	public static function set($var, $value) {
		$_SESSION['_ffw_'][$var] = $value;
	}

	/**
	 *	\brief Pega o valor de uma variável de sessão
	 */
	public static function get($var) {
		if (isset($_SESSION['_ffw_'][$var])) {
			return $_SESSION['_ffw_'][$var];
		}

		return NULL;
	}

	/**
	 *	\brief Coloca todos os dados armazenados na sessão na variável fornecida
	 *
	 *	\return retorna \c true se tiver sucesso ou \c false se não houver sessão
	 */
	public static function get_all(&$retorno) {
		if (isset($_SESSION['_ffw_'])) {
			$retorno = $_SESSION['_ffw_'];
			return true;
		}

		return false;
	}

	/**
	 *	\brief Remove uma variável de sessão
	 */
	public static function unregister($var) {
		unset($_SESSION['_ffw_'][$var]);
	}
}
?>