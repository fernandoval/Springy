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
		Framework URI class
	------------------------------------------------------------------------------------ --- -- - */

class Uri extends Kernel {
	//private static $limiteParams = 5;
	private static $segments = array();
	private static $get_params = array();
	private static $page;

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Lê a URLs (em modo re-write) e transforma em variáveis $_GET
	    -------------------------------------------------------------------------------- --- -- - */
	public static function parse_uri() {
		// [pt-br] Monta a URI
		if (isset($_GET['supervar'])) {
			$UriString = $_GET['supervar'];
			unset($_GET['supervar']);
		} elseif (is_array($_GET) AND count($_GET) == 1 AND trim(key($_GET), '/') != '') {
			$UriString = key($_GET);
		} else {
			$pos = strpos($_SERVER['QUERY_STRING'], '&');
			if ($pos === false) {
				$path = $_SERVER['QUERY_STRING'];
			} else {
				$path = substr($_SERVER['QUERY_STRING'], 1, $pos);
			}
			$UriString = trim($path, '/');
		}

		// [pt-br] Processa a URI
		$Segments = array();
		foreach(explode("/", preg_replace("|/*(.+?)/*$|", "\\1", $UriString)) as $val) {
			$val = trim($val);

			if ($val != '') {
				$Segments[] = $val;
			}
		}

		// [pt-br] Define o primeiro segmento da URI como sendo a página solicitada
		self::$page = (trim($Segments[0]) ? $Segments[0] : 'index');
		array_shift($Segments);

		// [pt-br] Guarda os demais segmentos da URI
		foreach ($Segments as $segment) {
			if (trim($segment) != '') {
				self::$segments[] = $segment;
			}
		}

		// [pt-br] Guarda os parâmetros passados por GET na URL
		foreach ($_GET as $key => $value) {
			self::$get_params[ $key ] = $value;
			unset($_GET[ $key ]);
		}

		/*
		if (parent::get_conf('system', 'rewrite_url') == false) {
			$pos = strpos($_SERVER['QUERY_STRING'], '&');
			if ($pos === false) {
				$_GET['supervar'] = $_SERVER['QUERY_STRING'];
			} else {
				$_GET['supervar'] = substr($_SERVER['QUERY_STRING'], 1, $pos);
			}
			unset($pos);
		} else if (!isset($_GET['supervar'])) {
			$_GET['supervar'] = '';
		}

		//print_r($_GET);die;
		//         PAG        /    param0        /    param1        /    param2        /    param3        /    param4        /

		$exp = '^([a-zA-Z0-9\.\-_,@=\+\$#]*)[/]?([a-zA-Z0-9\.\-_\,\@\=\+\$\*]*)[/]?([a-zA-Z0-9\.\-_\,\@\=\+\$\*]*)[/]?([a-zA-Z0-9\.\-_\,\@\=\+\$\*]*)[/]?([a-zA-Z0-9\.\-_\,\@\=\+\$\*]*)[/]?([a-zA-Z0-9\.\-_\,\@\=\+\$\*]*)[/]?('.parent::get_conf('system', 'ext_file_url').')(.*)$';
		ereg($exp, $_GET['supervar'], $params);
		unset($exp, $_GET['supervar']);

		self::$page = (trim($params[1]) ? $params[1] : 'index');

		for ($i=2; $i<=2+self::$limiteParams; $i++) {
			if (!isset($params[ $i ]) || trim($params[ $i ])=='') {
				break;
			} else {
				self::$segments[] = $params[$i];
			}
		}

		// $_SERVER['QUERY_STRING']
		foreach ($_GET as $key => $value) {
			self::$get_params[ $key ] = $value;
			unset($_GET[ $key ]);
		}
		*/
	}

	/*
		[pt-br] Retorna os parametros atuais no formato "re-write"
	*/
	public static function getCurrentURL() {
		return $_SERVER['REQUEST_URI'];
	}

	public static function getCurrentUrlReWrite( $http = true, $get_params = true ) {
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Retorna a página atual
	    -------------------------------------------------------------------------------- --- -- - */
	public static function current_page() {
		return self::$page;
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Retorna o segmento da URI selecionado
	    -------------------------------------------------------------------------------- --- -- - */
	public static function get_segment( $param ) {
		if (array_key_exists($param, self::$segments)) {
			return self::$segments[ $param ];
		}
		return false;
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Retorna o valor de um parâmetro GET
	    -------------------------------------------------------------------------------- --- -- - */
	public static function _GET( $var ) {
		if (array_key_exists($var, self::$get_params)) {
			return self::$get_params[ $var ];
		}
		return false;
	}

	public static function setQueryString( $var, $value ) {
		self::$get_params[ $var ] = $value;
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Monta uma URL
	    -------------------------------------------------------------------------------- --- -- - */
	public static function build($page = '', $params = array(), $query = array(), $forceRewrite = false) {
		$url = 'http://' . parent::get_conf('system', 'uri') . '/';

		if (parent::get_conf('system', 'rewrite_url') == false && $forceRewrite == false) {
			$url .= '?';
		}

		if (self::$page != '') {
			$url .= $page;

			for ($i=0; $i<count($params); $i++) {
				if ($params[ $i ] != 'index') {
					$url .= '/' . (($i > 1) ? self::simplifier($params[ $i ]) : $params[ $i ]);
				}
			}
		}

		if (parent::get_conf('system', 'ext_file_url')) {
			$url .= parent::get_conf('system', 'ext_file_url');
		}

		$param = '';
		if (count($query)) {
			foreach ($query as $var => $value) {
				if (!in_array($var, array('agsistemas'))) {
					$param .= (empty($param) ? '?' : '&') . $var . '=' . $value;
				}
			}
		}

		return $url . $param;
	}

	public static function mountStatic($static) {
		return 'http://' . parent::get_conf('urls', 'uri') . '/' . parent::get_conf('urls', $static);
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Manda o header de redirecionamento para uma URL
	    -------------------------------------------------------------------------------- --- -- - */
	public static function redir($url, $header=302) {
		header('Location: ' . $url, $header);
		die;
	}

	/*
		Transforma letras acentuadas em letras "sem acento", espaçoes em "_" (underline) e remove outros caracteres.
	*/
	public static function simplifier($txt) {
		$txt = mb_strtolower( trim($txt) );

		$txt = ereg_replace('[áàâãåäªÁÀÂÄÃ]', 'a', $txt);
		$txt = ereg_replace('[íìîïÍÌÎÏ]', 'i', $txt);
		$txt = ereg_replace('[éèêëÉÈÊË]', 'e', $txt);
		$txt = ereg_replace('[óòôõöºÓÒÔÕÖ]', 'o', $txt);
		$txt = ereg_replace('[úùûüÚÙÛÜ]', 'u', $txt);
		$txt = ereg_replace('[ñÑ]', 'n', $txt);
		$txt = ereg_replace('[çÇ]', 'c', $txt);

		$txt = ereg_replace('[ ]+', '-', $txt);
		$txt = ereg_replace('[^a-z0-9_.\-]', '', $txt);
		$txt = ereg_replace('[--]+', '-', $txt);
		$txt = ereg_replace('[__]+', '_', $txt);

		return $txt;
	}
}
?>