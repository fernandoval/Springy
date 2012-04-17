<?php
/** \mainpage FVAL PHP Framework for Web Applications
 *
 *  FVAL PHP Framework for Web Applications
 *
 *  \version 1.0.0
 *  Copyright (c) 2007-2009 FVAL Consultoria e Informática Ltda.
 *
 *  http://www.fval.com.br
 *
 *  \author Fernando Val  - fernando.val@gmail.com
 *  \author Lucas Cardozo - lucas.cardozo@gmail.com
 *
 */

/*  ------------------------------------------------------------------------------------ --- -- -
	FVAL PHP Framework for Web Sites

	Copyright (c) 2007-2009 FVAL - Consultoria e Informática Ltda.
	Copyright (C) 2009 Fernando Val
	Copyright (C) 2009 Lucas Cardozo

	http://www.fval.com.br

	Developer team:
		Fernando Val  - fernando.val@gmail.com
		Lucas Cardozo - lucas.cardozo@gmail.com

	Framework version:
		1.0.0

	Script version:
		0.9.6

	This script:
		Framework startup script
	------------------------------------------------------------------------------------ --- -- - */

$FWGV_START_TIME = microtime(true); // Memoriza a hora do início do processamento

// Carrega a configuração global do sistema
if (!file_exists('sysconf.php')) {
	header('Content-type: text/html; charset=UTF-8', true, 500);
	die('Internal System Error on Startup');
}
require('sysconf.php');

/*  ------------------------------------------------------------------------------------ --- -- -
	[pt-br] Função de carga automática de classes
	------------------------------------------------------------------------------------ --- -- - */
function __autoload($classe) {
	if (file_exists($GLOBALS['SYSTEM']['LIBRARY_PATH'] . DIRECTORY_SEPARATOR . $classe . '.php')) {
		require_once($GLOBALS['SYSTEM']['LIBRARY_PATH'] . DIRECTORY_SEPARATOR . $classe . '.php');
	} elseif (file_exists($GLOBALS['SYSTEM']['USER_CLASS_PATH'] . DIRECTORY_SEPARATOR . $classe . '.class.php')) {
		require_once($GLOBALS['SYSTEM']['USER_CLASS_PATH'] . DIRECTORY_SEPARATOR . $classe . '.class.php');
	}
}

/*  ------------------------------------------------------------------------------------ --- -- -
	[pt-br] Função de tratamento de erros para impedir que a classe de erros seja carregada
	desnecessariamente
	------------------------------------------------------------------------------------ --- -- - */
error_reporting(E_ALL);
function FW_ErrorHandler($errno, $errstr, $errfile, $errline) {
	Errors::error_handler($errno, $errstr, $errfile, $errline);
}
set_error_handler('FW_ErrorHandler');

/*  ------------------------------------------------------------------------------------ --- -- -
	[pt-br] Início do script
	------------------------------------------------------------------------------------ --- -- - */

// [pt-br] Verifica se é ambiente de desenvolvimento
if (Kernel::get_conf('system', 'development')) {
	ini_set('display_errors', 1);
} else {
	ini_set('display_errors', 0);
}


// [pt-br] Envia o charset
header('Content-Type: text/html; charset='.$GLOBALS['SYSTEM']['CHARSET'], true);

ini_set('zlib.output_compression', 'on');
ini_set('mbstring.internal_encoding', $GLOBALS['SYSTEM']['CHARSET']);
ini_set('default_charset', $GLOBALS['SYSTEM']['CHARSET']);

// [pt-br] Verifica se o sistema está em manutenção
if ($GLOBALS['SYSTEM']['MAINTENANCE']) {
	Errors::display_error(503, 'The system is under maintenance');
}

// [pt-br] Resolve a URI e monta as variáveis internas
URI::parse_uri();

// [pt-br] Verifica se a controller _global existe
$path = $GLOBALS['SYSTEM']['CONTROLER_PATH'] . DIRECTORY_SEPARATOR . '_global.php';
if (file_exists($path)) {
	require_once($path);
	unset($path);

	$pageClassName = 'Global_Controller';
	if (class_exists($pageClassName)) {
		new $pageClassName;
	}
}

// [pt-br] Procura a controller correta e corrige a página atual se necessário
$path = $GLOBALS['SYSTEM']['CONTROLER_PATH'];
$segment = 0;
while (URI::get_segment($segment, false)) {
	$path .= DIRECTORY_SEPARATOR . URI::get_segment($segment, false);
	$file = $path . '.page.php';
	if (file_exists($file)) {
		$controller = $file;
		URI::set_current_page($segment);
		break;
	} elseif (is_dir($path) && (!URI::get_segment($segment + 1, false))) {
		$file = $path . DIRECTORY_SEPARATOR . 'index.page.php';
		if (file_exists($file)) {
			$controller = $file;
			URI::add_segment('index');
			URI::set_current_page($segment + 1);
			break;
		}
	} elseif (is_dir($path)) {
		$segment++;
	} else {
		break;
	}
}

// [pt-br] Se foi definido uma Controller, carega
if (isset($controller)) {
	ob_start();

	// [pt-br] Carrega a controller
	require_once($controller);

	// [pt-br] Inicializa a controller
	$ControllerClassName = str_replace('-', '_', URI::current_page()) . '_Controller';
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

		if (!Template::template_exists(URI::current_page()) && Template::template_exists('_template')) {
			Template::set_template('_template');
		}
	} else {
		// [pt-br] Nenhuma controller definida e não está usando CMS ou não há artigo correspondente
		Errors::display_error(404, URI::relative_path_page() . '/' . URI::current_page());
	}
}

// [pt-br] se o template estiver carregado, imprime
if (Template::is_started()) {
	Template::display();
}

// [pt-br] se tiver algum debug, utiliza-o
Kernel::debug('Tempo de execução de página: ' . number_format(microtime(true) - $FWGV_START_TIME, 6) . ' segundos');
Kernel::debug_print();

ob_end_flush();
?>