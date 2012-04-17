<?php
/*  ------------------------------------------------------------------------------------ --- -- -
	FVAL PHP Framework for Web Sites

	Copyright (C) 2009 FVAL - Consultoria e Informática Ltda.
	Copyright (C) 2009 Fernando Val
	Copyright (C) 2009 Lucas Cardozo

	http://www.fval.com.br

	Developer team:
		Fernando Val  - fernando.val@gmail.com
		Lucas Cardozo - lucas.cardozo@gmail.com

	Framework version:
		1.0.0

	Script version:
		1.0.0

	This script:
		Framework session class
	------------------------------------------------------------------------------------ --- -- - */

class Session extends {
	private static $started = false;
	
	function __construct() {
		if (!self::$started) {
			self::$started = true;
			session_start();
		}
	}
	
	public function isRegistered($sub, $var) {
		return isset($_SESSION[$sub][$var]);
	}
	
	public function regSession($sub, $var, $value) {
		$_SESSION[$sub][$var] = $value;
	}
	
	public function getAllGroupRegisteredSession(&$retorno) {
		if (isset($_SESSION[$sub])) {
			$retorno = $_SESSION[$sub];
			return true;
		}
		
		return false;
	}
	
	public function getRegisteredSession($sub, $var, &$retorno) {
		if (isset($_SESSION[$sub][$var])) {
			$retorno = $_SESSION[$sub][$var];
			return true;
		}
		
		return false;
	}
	
	public function unRegSession($sub, $var='') {
		if ($var == '') {
			unset($_SESSION[$sub]);
		} else {
			unset($_SESSION[$sub][$var]);
		}
	}
}
?>