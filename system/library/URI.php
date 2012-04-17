<?php
/**
 *	FVAL PHP Framework for Web Applications\n
 *	Copyright (c) 2007-2009 FVAL Consultoria e Informática Ltda.
 *
 *	\warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\version 0.9.9
 *
 *	\brief Classe para tratamento de URI
 */

class URI extends Kernel {
	/// String da URI
	private static $uri_string = '';
	/// Array dos segmentos da URI
	private static $segments = array();
	/// Array da relação dos parâmetros recebidos por GET
	private static $get_params = array();
	/// Índice do segmento que determina a página atual
	private static $segment_page = 0;


	/**
	 *	\brief Lê a URLs (em modo re-write) e inicializa a variável $uri_string interna
	 *
	 *	\return \c true se houve sucesso no processo e \c false em caso contrário
	 */
	private static function _fetch_uri_string() {
		if (is_array($_GET) && count($_GET) == 1 && (trim(key($_GET), '/') != '') && empty($_GET[key($_GET)])) {
			self::$uri_string = key($_GET);
			return true;
		}

		// [pt-br] A variável PATH_INFO existe?
		$path = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');
		if (trim($path, '/') != '' && $path != '/' . pathinfo(__FILE__, PATHINFO_BASENAME)) {
			self::$uri_string = trim($path, '&');
			return true;
		}

		// [pt-br] Não há PATH_INFO? A entrada QUERY_STRING existe?
		$path = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
		if (trim($path, '/') != '') {
			self::$uri_string = $path;
			return true;
		}

		// [pt-br] Não há QUERY_STRING? Então a variável ORIG_PATH_INFO existe?
		$path = (isset($_SERVER['ORIG_PATH_INFO'])) ? $_SERVER['ORIG_PATH_INFO'] : @getenv('ORIG_PATH_INFO');
		if (trim($path, '/') != '' && $path != '/' . pathinfo(__FILE__, PATHINFO_BASENAME))
		{
			// [pt-br] remove caminho e informações do script, então temos uma boa URI
			self::$uri_string = str_replace($_SERVER['SCRIPT_NAME'], '', $path);
			return true;
		}

		// [pt-br] Se esgotaram todas as opções...
		self::$uri_string = '';
		return false;
	}

	/**
	 *	\brief Lê a URLs (em modo re-write) e transforma em variáveis $_GET
	 *
	 *	\note Este método não retorna valor
	 */
	public static function parse_uri() {
		self::_fetch_uri_string();

		$UriString = trim(self::$uri_string, '/');

		// [pt-br] Processa a URI
		$Segments = array();
		foreach(explode("/", preg_replace("|/*(.+?)/*$|", "\\1", $UriString)) as $val) {
			$val = trim($val);

			if ($val != '') {
				$Segments[] = $val;
			}
		}
		if (empty($Segments)) {
			$Segments[] = 'index';
		}

		// [pt-br] Define o primeiro segmento da URI como sendo a página solicitada
		//self::$segment_page = (trim($Segments[0]) ? $Segments[0] : 'index');
		//array_shift($Segments);

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
	}

	/**
	 *	\brief Retorna a URI atual
	 *
	 *	\return A string da URI
	 */
	public static function get_uri_string() {
		return self::$uri_string;
	}

	/**
	 *	\brief Retorna a página atual
	 *
	 *	\return O segmento que representa a página atual
	 */
	public static function current_page() {
		return self::get_segment(self::$segment_page, false);
	}

	/**
	 *	\brief Retorna o caminho relativo da página atual
	 *
	 *	\return Uma string contendo o caminho relativo à página atual
	 */
	public static function relative_path_page() {
		$path = '';
		for ($i = 0; $i < self::$segment_page; $i++) {
			$path .= (empty($path) ? '' : DIRECTORY_SEPARATOR) . self::get_segment($i, false);
		}
		return $path;
	}

	/**
	 *	\brief Define o segmento relativo à página atual
	 *
	 *	@param[in] $segment_num número relativo ao segmento da URI
	 *	\return \c trus se definiu o segmento relativo à página atual e \c false em caso contrário
	 */
	public static function set_current_page($segment_num) {
		if (self::get_segment($segment_num, false)) {
			self::$segment_page = $segment_num;
			return true;
		}

		return false;
	}

	/**
	 *	\brief Retorna o segmento da URI selecionado
	 *
	 *	@param[in] $segment_num O número do segmento desejado
	 *	@param[in] $relative_to_page Flag (true/false) que determina se o segmento desejado é 
	 *		relativo ao segmento que determina a página atual. Default = true
	 *	\return o valor do segmento ou \c false caso o segmento não exista
	 */
	public static function get_segment($segment_num, $relative_to_page=true) {
		if ($relative_to_page) {
			$segment_num += (1 + self::$segment_page);
		}
		if (array_key_exists($segment_num, self::$segments)) {
			return self::$segments[ $segment_num ];
		}
		return false;
	}

	/**
	 *	\brief Adiciona um novo segmento de URI
	 *
	 *	@param[in] $segment String contendo o valor do segmento
	 *	\return \c true se tiver sucesso e \c false em caso contrário
	 */
	public static function add_segment($segment) {
		if (trim($segment) != '') {
			self::$segments[] = $segment;
			return true;
		}
		return false;
	}

	/**
	 *	\brief Retorna o valor de um parâmetro GET
	 *
	 *	@param[i] $var String contendo o nome da variável desesada
	 *	\return O valor da variável, caso exista, ou \c false caso a variável não exista
	 */
	public static function _GET($var) {
		if (array_key_exists($var, self::$get_params)) {
			return self::$get_params[ $var ];
		}
		return false;
	}

	/**
	 *	\brief get_param é um apelido para _GET
	 *	\see _GET
	 */
	public static function get_param($var) {
		return self::_GET($var);
	}

	/**
	 *	\brief Define o valor de um parâmetro
	 *
	 *	@param[in] $var String contendo o nome da variável a ser definida
	 *	@param[in] $value O valor da variável
	 */
	public static function set_param( $var, $value ) {
		self::$get_params[ $var ] = $value;
	}

	/**
	 *	\brief Monta uma URL
	 *
	 *	@param[in] $segments Array contendo os segmentos da URI
	 *	@param[in] $query Array contendo as variáveis a serem passadas via na URL GET
	 *	@param[in] $forceRewrite flag (true/false) que determina se o formato SEF deve ser forçado
	 *	\return Uma \c string contendo a URL
	 */
	public static function build_url($segments=array(), $query=array(), $forceRewrite=false) {
		$url = str_replace('//', '/', parent::get_conf('system', 'uri') . '/');

		// Se rewrite de URL está desligado e não está sendo forçado, acrescenta ? à URL
		if (parent::get_conf('system', 'rewrite_url') === false && $forceRewrite === false) {
			$url .= '?';
		}

		// Monta a URI
		$uri = '';
		for ($i=0; $i < count($segments); $i++) {
			if ($params[ $i ] != 'index') {
				$url .= (empty($uri) ? '' : '/') . self::slug_generator($params[ $i ]);
			}
		}
		$url .= $uri;

		/*if (parent::get_conf('system', 'ext_file_url')) {
			$url .= parent::get_conf('system', 'ext_file_url');
		}*/

		// Monta os parâmetros a serem passados por GET
		$param = '';
		foreach ($query as $var => $value) {
			$param .= (empty($param) ? '?' : '&') . $var . '=' . urlencode($value);
		}

		return 'http://' . $url . $param;
	}

	/**
	 *	\brief Manda o header de redirecionamento para uma URL
	 *
	 *	Este método envia o cabeçalho (header) de redirecionamento para o usuário e termina a
	 *	execução do sistema.
	 *
	 *	@param[in] $url A URL para qual o usuário deve ser redirecionado
	 *	@param[in] $header Um inteiro com o código de redirecionamento
	 *		(302 = permanente, 301 = temporário, etc.).\n
	 *		Se omitido usa 302 por padrão.
	 */
	public static function redirect($url, $header=302) {
		if (ob_get_level() > 0) ob_clean();

		header('Status: ' . $header);
		header('Location: ' . $url, $header);
		exit;
	}

	/**
	 *	\brief Gera o slug de um string
	 *
	 *	@param[in] $txt String a ser convertida em slug
	 *	@paran[in] $space String que será usada para substituir os espaços em $txt.
	 *		Se for omitido utiliza '-' como padrão.
	 *	\return Uma string com o slug
	 */
	public static function slug_generator($txt, $space='-') {
		$txt = mb_strtolower(trim($txt));

		if (mb_check_encoding($txt, 'UTF-8')) {
			$txt = Strings_UTF8::remove_accented_chars($txt);
		} else {
			$txt = Strings_ANSI::remove_accented_chars($txt);
		}

		$txt = mb_ereg_replace('[  ]+', ' ', $txt);
		$txt = mb_ereg_replace('[--]+', '-', $txt);
		$txt = mb_ereg_replace('[__]+', '_', $txt);
		$txt = mb_ereg_replace('[ ]+', $space, $txt);
		$txt = mb_ereg_replace('[^a-z0-9_.\-]', '', $txt);

		return $txt;
	}
}
?>