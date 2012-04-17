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
		Framework startup script
	------------------------------------------------------------------------------------ --- -- - */

$FWGV_START_TIME = microtime(true); // Memoriza a hora do início do processamento

// Carrega a configuração global do sistema
if (!file_exists('sysconf.php')) {
	header('Content-type: text/html; charset=UTF-8', true, 500);
	die('Internal System Error on Startup');
}
require('sysconf.php');

ini_set('zlib.output_compression', 'on');

/*  ------------------------------------------------------------------------------------ --- -- -
	[pt-br] Define a função de carga automática de classes
	------------------------------------------------------------------------------------ --- -- - */
function __autoload($classe) {
	if (file_exists($GLOBALS['SYSTEM']['LIBRARY_PATH'] . DIRECTORY_SEPARATOR . $classe . '.php')) {
		require $GLOBALS['SYSTEM']['LIBRARY_PATH'] . DIRECTORY_SEPARATOR . $classe . '.php';
	} elseif (file_exists($GLOBALS['SYSTEM']['USER_CLASS_PATH'] . DIRECTORY_SEPARATOR . $classe . '.class.php')) {
		require $GLOBALS['SYSTEM']['USER_CLASS_PATH'] . DIRECTORY_SEPARATOR . $classe . '.class.php';
	}
}

/*  ------------------------------------------------------------------------------------ --- -- -
	[pt-br] Define função para tratamento de erros para impedir que a classe de erros seja carregada
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

// [pt-br] Define o subdiretório das subclasses do Smarty
define('SMARTY_DIR', $GLOBALS['SYSTEM']['CLASS_PATH'] . DIRECTORY_SEPARATOR . 'Smarty' . DIRECTORY_SEPARATOR);

// [pt-br] Envia o charset
header('Content-Type: text/html; charset='.$GLOBALS['SYSTEM']['CHARSET'], true);

// [pt-br] Verifica se o sistema está em manutenção
if ($GLOBALS['SYSTEM']['MAINTENANCE']) {
	Errors::display_error(503, 'The system is under maintenance');
}

// [pt-br] Resolve a URI e monta as variáveis internas
Uri::parse_uri();

// [pt-br] Verifica se a controller existe
$path = Kernel::get_conf('system', 'controller_path') . DIRECTORY_SEPARATOR . Uri::current_page() . '.php';
if (!file_exists($path)) {
	Errors::display_error(404, $path);
}

// [pt-br] Carrega a controller
require_once($path);
unset($path);

// [pt-br] Inicializa a controller
$pageClassName = Uri::current_page() . '_Controller';
if (class_exists($pageClassName)) {
	new $pageClassName;
}

// [pt-br] se tiver algum debug, utiliza-o
Kernel::debug('Tempo de execução de página: ' . number_format(microtime(true) - $FWGV_START_TIME, 6) . ' segundos');
Kernel::debug_print();
?>