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
		Framework template class
	------------------------------------------------------------------------------------ --- -- - */

class Template extends Kernel {
	private static $template_obj = NULL;
	private static $template_name = NULL;

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Inicializa a classe de template
	    -------------------------------------------------------------------------------- --- -- - */
	public static function start() {
		self::$template_obj = new Smarty();

		self::$template_obj->cache_lifetime = 0;
		self::$template_obj->caching = false;
		self::$template_obj->force_compile = true;

		self::set_compiled_template_path( self::get_conf('system', 'compiled_template_path') );
		self::set_template_path( self::get_conf('system', 'template_path') );
		self::set_config_dif( self::get_conf('system', 'template_config_path') );
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Retorna o objeto da classe de template
	    -------------------------------------------------------------------------------- --- -- - */
	public static function get_template_obj() {
		return self::$template_obj;
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Define o cacheamento dos templates
	    -------------------------------------------------------------------------------- --- -- - */
	public static function caching($bool) {
		self::$template_obj->caching = $bool;
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Retorna a página montada
	    -------------------------------------------------------------------------------- --- -- - */
	public static function fetch() {
		error_reporting(E_ALL^E_NOTICE);
		restore_error_handler();

		if (self::$template_name !== NULL) {
			$tpl = self::$template_name;
		} else {
			$tpl = Uri::current_page();
		}

		if ($smarty->template_exists($tpl . '.tpl.html')) {
			return self::$template_obj->fetch($tpl . '.tpl.html');
		} else {
			Error::display_error(404, $tpl . '.tpl.html');
		}
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Faz a saída da página montada
	    -------------------------------------------------------------------------------- --- -- - */
	public static function display() {
		error_reporting(E_ALL^E_NOTICE);
		restore_error_handler();

		if (self::$template_name !== NULL) {
			$tpl = self::$template_name;
		} else {
			$tpl = Uri::current_page();
		}

		if ($smarty->template_exists($tpl . '.tpl.html')) {
			self::$template_obj->display($tpl . '.tpl.html');
		} else {
			Error::display_error(404, $tpl . '.tpl.html');
		}
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Verifica se o template está cacheado
	    -------------------------------------------------------------------------------- --- -- - */
	public static function is_cached() {
		return (self::$template_obj->is_cached(self::$template_name, self::$smartyTplId));
	}

	// seta as principais variaveis, usadas em TODOS os tpls
	public static function smartySetCommon() {
		self::smartyAssign('siteUrl', Uri::mount());
		self::smartyAssign('GoogleAnalytics', self::get_conf('system', 'GoogleAnalytics'));
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Define o local dos arquivos de template compilados
	    -------------------------------------------------------------------------------- --- -- - */
	public static function set_compiled_template_path($path) {
		if (self::$template_obj !== NULL) {
			if (!file_exists($path)) {
				mkdir($path, 0755, true);
			}
			self::$template_obj->compile_dir = $path;
		}
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Define o local dos arquivos de template compilados
	    -------------------------------------------------------------------------------- --- -- - */
	public static function set_config_dif($path) {
		if (self::$template_obj !== NULL) {
			if (!file_exists($path)) {
				mkdir($path, 0755, true);
			}
			self::$template_obj->config_dir = $path;
		}
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Define o arquivos de template
	    -------------------------------------------------------------------------------- --- -- - */
	public static function set_template($tpl) {
		self::$template_name = ((is_array($tpl)) ? join(DIRECTORY_SEPARATOR, $tpl) : $tpl);
	}

	// seta o ID do cache
	public static function smartySetTplId($id, $addLogin=false) {
		if ($addLogin !== false) {
			$login = new Login($addLogin);
		}

		self::$smartyTplId = $id . (($addLogin !== false && $login->isLoged()) ? '_' . $login->getRegistered('user') : '');
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Define o local dos arquivos de template
	    -------------------------------------------------------------------------------- --- -- - */
	public static function set_template_path($path) {
		self::$template_obj->template_dir = $path;
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Define variável do template
	    -------------------------------------------------------------------------------- --- -- - */
	public static function assign($var, $value) {
		self::$template_obj->assign($var, $value);
	}
}
?>