<?php
/**
 *	FVAL PHP Framework for Web Applications\n
 *	Copyright (c) 2007-2011 FVAL Consultoria e Informática Ltda.\n
 *	Copyright (c) 2007-2011 Fernando Val\n
 *	Copyright (c) 2009-2011 Lucas Cardozo
 *
 *	\warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\version 2.2.1
 *
 *	\brief Classe de tratamento de templates
 */

require_once 'Smarty' . DIRECTORY_SEPARATOR . 'Smarty.class.php';

// Define o subdiretório das subclasses do Smarty
//define('SMARTY_DIR', $GLOBALS['SYSTEM']['LIBRARY_PATH'] . DIRECTORY_SEPARATOR . 'Smarty' . DIRECTORY_SEPARATOR);

class Template extends Kernel {
	private static $started = false;
	private static $template_obj = NULL;
	private static $template_name = NULL;
	private static $template_name_sufix = '.tpl.html';
	private static $template_cache_id = "";
	private static $template_compile_id = "";
	private static $template_vars = array();

	/**
	 *	\brief Inicializa a classe de template
	 */
	public static function start($tpl=NULL) {
		if (self::$started) {
			self::set_template($tpl);
			return true;
		}

		// Verifica o sub-dir
		if (is_dir(parent::get_conf('template', 'template_path') . DIRECTORY_SEPARATOR . URI::current_page())) {
			$path = URI::current_page();
		} else {
			$path = 'default';
		}

		// Inicializa a classe de template
		if (self::$template_obj === NULL) {
			self::$template_obj = new Smarty();
		}

		//self::$template_obj->use_sub_dirs = true;
		self::$template_obj->cache_lifetime = 0;
		self::$template_obj->caching = false;
		self::$template_obj->force_compile = parent::get_conf('system', 'debug');

		// Limpa qualquer variável que por ventura exista no template
		self::$template_obj->clearAllAssign();

		// Ajusta os caminhos de template
		self::set_template_path( parent::get_conf('template', 'template_path') );
		self::set_compiled_template_path( parent::get_conf('template', 'compiled_template_path') );
		self::set_config_dif( parent::get_conf('template', 'template_config_path') );

		if ($tpl) {
			self::set_template($tpl);
		}

		self::$started = true;

		// Iniciliza as variáveis padrão de template
		if (parent::get_conf('uri', 'common_urls')) {
			if (!parent::get_conf('uri', 'register_method_set_common_urls')) {
				foreach(parent::get_conf('uri', 'common_urls') as $var => $value) {
					if (isset($value[2])) {
						Template::assign_default_var($var, URI::build_url($value[0], $value[1], $value[2]));
					} else if (isset($value[1])) {
						Template::assign_default_var($var, URI::build_url($value[0], $value[1]));
					} else {
						Template::assign_default_var($var, URI::build_url($value[0]));
					}
				}
			} else if (Kernel::get_conf('uri', 'register_method_set_common_urls')) {
				$toCall = Kernel::get_conf('uri', 'register_method_set_common_urls');
				if ($toCall['static']) {
					if (!isset($toCall['method'])) {
						throw new Exception('You need to determine which method will be executed.', 500);
					}

					//$toCall['class']::$toCall['method'];
				} else {
					$obj = new $toCall['class'];
					if (isset($toCall['method']) && $toCall['method']) {
						$obj->$toCall['method'];
					}
				}
			}
		}

		self::assign_default_var('HOST', URI::build_url());
		self::assign_default_var('CURRENT_PAGE_URI', URI::current_page_uri());

		return true;
	}

	/**
	 *	\brief Finaliza a classe de template
	 */
	public static function stop() {
		if (!self::$started) return true;

		// Limpa qualquer variável que por ventura exista no template
		self::$template_obj->clearAllAssign();

		self::$template_name = NULL;
		self::$started = false;

		return true;
	}

	/**
	 *	\brief Verifica o template ideal de acordo com a página
	 */
	private static function _set_template_paths() {
		// Pega o caminha relativo da página atual
		$relative_path_page = URI::relative_path_page();

		// Se o nome do template não foi informado, define como sendo a página atual
		if (self::$template_name === NULL) {
			self::$template_name = URI::get_class_controller();
		}

		// Monta o caminho do diretório do arquivo de template
		$path = parent::get_conf('template', 'template_path') . (empty($relative_path_page) ? '' : DIRECTORY_SEPARATOR) . $relative_path_page;

		// Verifica se existe o diretório e dentro dele um template com o nome da página e
		// havendo, usa como caminho relativo adicionao. Se não houver, limpa o caminho relativo.
		if (is_dir($path) && file_exists($path . DIRECTORY_SEPARATOR . self::$template_name . self::$template_name_sufix)) {
			$relative_path = (empty($relative_path_page) ? '' : DIRECTORY_SEPARATOR) . $relative_path_page;
		} else {
			$relative_path = '';
		}

		// Ajusta os caminhos de template
		self::set_template_path( parent::get_conf('template', 'template_path') . $relative_path );
		self::set_compiled_template_path( parent::get_conf('template', 'compiled_template_path') . $relative_path );
		self::set_config_dif( parent::get_conf('template', 'template_config_path') . $relative_path );

		// Se o arquivo de template não existir, exibe erro 404
		if (!self::$template_obj->templateExists(self::$template_name . self::$template_name_sufix)) {
			Errors::display_error(404, self::$template_name . self::$template_name_sufix);
		}

		return true;
	}

	/**
	 *	\brief Verifica se o template foi inicializado
	 */
	public static function is_started() {
		return self::$started;
	}
	public static function started() {
		return self::is_started();
	}

	/**
	 *	\brief Retorna o objeto da classe de template
	 */
	public static function get_template_obj() {
		return self::$template_obj;
	}

	/**
	 *	\brief Define o cacheamento dos templates
	 */
	public static function caching($bool) {
		self::$template_obj->caching = $bool;
	}

	/**
	 *	\brief Retorna a página montada
	 */
	public static function fetch() {
		if (!self::$started) return false;

		error_reporting(E_ALL^E_NOTICE);
		restore_error_handler();

		self::set_default_vars();
		self::_set_template_paths();

		self::$started = false;

		return self::$template_obj->fetch(self::$template_name . self::$template_name_sufix, self::$template_cache_id, self::$template_compile_id);
	}

	/**
	 *	\brief Faz a saída da página montada
	 */
	public static function display() {
		if (!self::$started) return false;

		echo self::fetch();
	}

	/**
	 *	\brief Verifica se o template está cacheado
	 */
	public static function is_cached() {
		return (self::$template_obj->isCached(self::$template_name . self::$template_name_sufix, self::$template_cache_id, self::$template_compile_id));
	}

	/**
	 *	\brief Define as variáveis padrão
	 */
	public static function set_default_vars() {
		foreach (self::$template_vars as $name => $value) {
			self::assign($name, $value);
		}
	}

	/**
	 *	\brief Define o local dos arquivos de template compilados
	 */
	public static function set_compiled_template_path($path) {
		if (self::$template_obj !== NULL) {
			if (!is_dir($path)) {
				mkdir($path, 0777, true);
			}
			self::$template_obj->compile_dir = $path;
		}
	}

	/**
	 *	\brief Define o local dos arquivos de template compilados
	 */
	public static function set_config_dif($path) {
		if (self::$template_obj !== NULL) {
			if (!is_dir($path)) {
				mkdir($path, 0777, true);
			}
			self::$template_obj->config_dir = $path;
		}
	}

	/**
	 *	\brief Define o arquivos de template
	 */
	public static function set_template($tpl) {
		self::$template_name = ((is_array($tpl)) ? join(DIRECTORY_SEPARATOR, $tpl) : $tpl);
	}

	/**
	 *	\brief Define o id do cache
	 */
	public static function set_cache_id($id) {
		self::$template_cache_id = $id;
	}

	/**
	 *	\brief Define o id da compilação
	 */
	public static function set_compile_id($id) {
		self::$template_compile_id = $id;
	}

	/**
	 *	\brief Define o local dos arquivos de template
	 */
	public static function set_template_path($path) {
		self::$template_obj->template_dir = $path;
	}

	/**
	 *	\brief Define uma variável do template
	 */
	public static function assign($var, $value=null, $nocache=false) {
		if (is_array($var)) {
			self::$template_obj->assign($var);
		} else {
			self::$template_obj->assign($var, $value, $nocache);
		}
	}

	/**
	 *	\brief Adiciona uma variável default de template
	 */
	public static function assign_default_var($name, $value) {
		self::$template_vars[$name] = $value;
	}

	/**
	 *	\brief Pega o valor de uma variável default de template
	 */
	public static function get_default_var($name) {
		return isset(self::$template_vars[$name]) ? self::$template_vars[$name] : NULL;
	}

	/**
	 *	\brief Limpa uma variável do template
	 */
	public static function clear_assign($var) {
		self::$template_obj->clearAssign($var);
	}

	/**
	 *	\brief clears the entire template cache
	 *
	 *	As an optional parameter, you can supply a minimum age in seconds the cache files must be before they will get cleared.
	 */
	public static function clear_all_cache($expire_time) {
		self::$template_obj->clearAllCache($expire_time);
	}

	/**
	 *	\brief Limpa o cache para o template corrente
	 */
	public static function clear_cache($expire_time) {
		self::$template_obj->clearCache(self::$template_name . self::$template_name_sufix, self::$template_cache_id, self::$template_compile_id, $expire_time);
	}

	/**
	 *	\brief Limpa a versão compilada do template atual
	 */
	public static function clear_compiled($exp_time) {
		self::$template_obj->clearCompiledTemplate(self::$template_name . self::$template_name_sufix, self::$template_compile_id, $exp_time);
	}

	/**
	 *	\brief Limpa variável de config definida
	 */
	public static function clear_config($var) {
		self::$template_obj->clearConfig($var);
	}

	/**
	 *	\brief Verifica se um arquivo de template existe
	 */
	public static function template_exists($tpl) {
		return self::$template_obj->templateExists($tpl . self::$template_name_sufix);
	}
}
