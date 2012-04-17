<?php
/** \mainpage FVAL PHP Framework for Web Applications
 *
 *  FVAL PHP Framework for Web Applications
 *
 *  \version 1.2.3
 *
 *	Copyright (c) 2007-2011 FVAL Consultoria e Informática Ltda.\n
 *	Copyright (c) 2007-2011 Fernando Val\n
 *	Copyright (c) 2009-2011 Lucas Cardozo
 *
 *  http://www.fval.com.br
 *
 *  \author Fernando Val  - fernando.val@gmail.com
 *  \author Lucas Cardozo - lucas.cardozo@gmail.com
 *
 */

$FWGV_START_TIME = microtime(true); // Memoriza a hora do início do processamento

// Carrega a configuração global do sistema
if (!file_exists('sysconf.php')) {
	header('Content-type: text/html; charset=UTF-8', true, 500);
	die('Internal System Error on Startup');
}
require('sysconf.php');

/**
 *	\brief Função de carga automática de classes
 */
function fwgv_autoload($classe) {
	if (file_exists($GLOBALS['SYSTEM']['LIBRARY_PATH'] . DIRECTORY_SEPARATOR . $classe . '.php')) {
		require_once($GLOBALS['SYSTEM']['LIBRARY_PATH'] . DIRECTORY_SEPARATOR . $classe . '.php');
	} elseif (file_exists($GLOBALS['SYSTEM']['USER_CLASS_PATH'] . DIRECTORY_SEPARATOR . $classe . '.class.php')) {
		require_once($GLOBALS['SYSTEM']['USER_CLASS_PATH'] . DIRECTORY_SEPARATOR . $classe . '.class.php');
	}
}
if (!spl_autoload_register('fwgv_autoload')) {
	header('Content-type: text/html; charset=UTF-8', true, 500);
	die('Internal System Error on Startup');
}

/**
 *	\brief Função de tratamento de erros para impedir que a classe de erros seja carregada desnecessariamente
 */
error_reporting(E_ALL);
function FW_ErrorHandler($errno, $errstr, $errfile, $errline, $localErro) {
	Errors::error_handler($errno, $errstr, $errfile, $errline, $localErro);
}
set_error_handler('FW_ErrorHandler');

/*  ------------------------------------------------------------------------------------ --- -- -
	Início do script
	------------------------------------------------------------------------------------ --- -- - */

ob_start();

// Envia o charset
header('Content-Type: text/html; charset='.$GLOBALS['SYSTEM']['CHARSET'], true);

//ini_set('zlib.output_compression', 'on');
ini_set('mbstring.internal_encoding', $GLOBALS['SYSTEM']['CHARSET']);
ini_set('default_charset', $GLOBALS['SYSTEM']['CHARSET']);
ini_set('date.timezone', $GLOBALS['SYSTEM']['TIMEZONE']);

// Resolve a URI e monta as variáveis internas
/*
	O parse precisa ficar aqui pois na linha de baixo possui um URI::_GET que só funciona após o parse
*/
$controller = URI::parse_uri();

if (Kernel::get_conf('system', 'developer_user') && Kernel::get_conf('system', 'developer_pass')) {
	if (URI::_GET( Kernel::get_conf('system', 'developer_user') ) == Kernel::get_conf('system', 'developer_pass')) {
		Cookie::set('_developer', true);
		/**
		 * A var $_SytemDeveloperOn é setada pois, quando o site tá em manutenção e é passado o user e pass para que o desenvolvedor veja o site,
		 * devido ao uso de Cookies, o site só aparece quando dá um refresh
		 */
		$_SytemDeveloperOn = true;
	} else if (URI::_GET( Kernel::get_conf('system', 'developer_user') ) == 'off') {
		Cookie::delete('_developer');
	}
}

if (Cookie::exists('_developer') || isset($_SytemDeveloperOn)) {
	Kernel::set_conf('system', 'maintenance', false);
	Kernel::set_conf('system', 'debug', true);
}

// apenas se o debug estiver ligado, verifica se o DBA (modo de exibição de SQLs) está ligado
if (Kernel::get_conf('system', 'debug') && Kernel::get_conf('system', 'dba_user')) {
	if (URI::_GET( Kernel::get_conf('system', 'dba_user') ) == Kernel::get_conf('system', 'developer_pass')) {
		Cookie::set('_dba', true);
	} else if (URI::_GET( Kernel::get_conf('system', 'dba_user') ) == 'off') {
		Cookie::delete('_dba');
	}
	
	if (Cookie::exists('_dba')) {
		Kernel::set_conf('system', 'sql_debug', true);
	}
}

if (Kernel::get_conf('system', 'debug')) {
	ini_set('display_errors', 1);
} else {
	ini_set('display_errors', 0);
}

// [pt-br] Verifica se o sistema está em manutenção
if (Kernel::get_conf('system', 'maintenance')) {
	Errors::display_error(503, 'The system is under maintenance');
}

// Verifica se a controller _global existe
$path = $GLOBALS['SYSTEM']['CONTROLER_PATH'] . DIRECTORY_SEPARATOR . '_global.php';
if (file_exists($path)) {
	require_once($path);
	unset($path);

	$pageClassName = 'Global_Controller';
	if (class_exists($pageClassName)) {
		new $pageClassName;
	}
}


// Se foi definido uma Controller, carega
if (!is_null($controller)) {
	// Carrega a controller
	require_once($controller);

	// Inicializa a controller
	$ControllerClassName = str_replace('-', '_', URI::get_class_controller()) . '_Controller';
	if (class_exists($ControllerClassName)) {
		$PageController = new $ControllerClassName;
		$PageMethod = URI::get_segment(0, true);
		if ($PageMethod && method_exists($PageController, $PageMethod)) {
			call_user_func(array($PageController, $PageMethod));
		} elseif (method_exists($PageController, '_default')) {
			call_user_func(array($PageController, '_default'));
		}
	} else {
		Errors::display_error(404, 'No ' . $ControllerClassName . ' on ' . $controller);
	}
	unset($controller);
} else {
	// [pr-br] Se a aplicação usa o mini CMS, carrega o artigo para a memória
	if ($GLOBALS['SYSTEM']['CMS'] && CMS::check_article_or_category()) {
		Template::start();

		if (CMS::article_is_loaded()) {
			CMS::load_article_to_template();
		} elseif (CMS::category_is_loaded()) {
			if (($pg = URI::get_segment(0, true)) && is_numeric($pg)) {
				$pg = (int)URI::get_segment(0, true);
				if ($pg < 1) $pg = 1;
			} else {
				$pg = 1;
			}
			$articles_per_page = Kernel::get_conf('cms', 'articles_per_page');
			CMS::load_category_to_template();
			CMS::load_articles_to_template(($pg - 1) * $articles_per_page, $articles_per_page);
		}

		if (!Template::template_exists(URI::get_class_controller()) && Template::template_exists('_template')) {
			Template::set_template('_template');
		}
	} else {
		if ($Page = URI::get_segment(0, false)) {
			if ($Page == 'framework' || $Page == 'about' || $Page == 'copyright' || $Page == 'credits' || $Page == 'fval' || $Page == '_') {
				Kernel::print_copyright();
			} elseif ($Page == '_pi_') {
				phpinfo();
				ob_end_flush();
				exit;
			}
		}

		// Nenhuma controller definida e não está usando CMS ou não há artigo correspondente
		Errors::display_error(404, URI::relative_path_page() . '/' . URI::current_page());
	}
}

// se o template estiver carregado, imprime
if (Template::is_started()) {
	Template::display();
}

// se tiver algum debug, utiliza-o
Kernel::debug_print();

ob_end_flush();