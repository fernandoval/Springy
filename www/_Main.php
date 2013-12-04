<?php
/**
 *  FVAL PHP Framework for Web Applications
 *  
 *	\copyright Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 *	\copyright Copyright (c) 2007-2013 Fernando Val\n
 *	\copyright Copyright (c) 2009-2013 Lucas Cardozo
 *
 *  http://www.fval.com.br
 *
 *	\brief		Script de inicialização da aplicação
 *  \version	1.8.10
 *  \author		Fernando Val  - fernando.val@gmail.com
 *  \author		Lucas Cardozo - lucas.cardozo@gmail.com
 *  \file
 *
 *  \defgroup framework Biblioteca do Framework
 *  @{
 *	@}
 *  \defgroup config Configurações do sistema
 *  @{
 *	@}
 *  \defgroup controllers Controladoras da aplicação
 *  @{
 *	@}
 *  \defgroup user_classes Classes da aplicação
 *  @{
 *	@}
 *  \defgroup templates Templates da aplicação
 *  @{
 *	@}
 *  
 *  \ingroup framework
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
	} else {
		// procura na user_classes

		// verifica se a classe utiliza o padrão com namespace (ou classe estática)
		// ex: class Oferta_Comercial_Static == arquivo user_classes/oferta/oferta-comercial.static.php

		preg_match('/^(?<class>[A-Za-z]+)(?<subclass>.*?)(?<type>_Static)?$/', $classe, $vars);

		$nameSpace = $classe = $vars['class'];

		if (!empty($vars['subclass'])) {
			$classe .= '-' . substr($vars['subclass'], 1);
		}

		if (isset($vars['type'])) {
			$classe .= '.static';
		} else {
			$classe .= '.class';
		}

		// procura a classe nas Libarys
		if (file_exists($GLOBALS['SYSTEM']['USER_CLASS_PATH'] . DIRECTORY_SEPARATOR . $nameSpace . DIRECTORY_SEPARATOR . $classe. '.php')) {
			require_once $GLOBALS['SYSTEM']['USER_CLASS_PATH'] . DIRECTORY_SEPARATOR . $nameSpace . DIRECTORY_SEPARATOR . $classe . '.php';
		} elseif (file_exists($GLOBALS['SYSTEM']['USER_CLASS_PATH'] . DIRECTORY_SEPARATOR . $classe . '.php')) {
			require_once $GLOBALS['SYSTEM']['USER_CLASS_PATH'] . DIRECTORY_SEPARATOR . $classe . '.php';
		}
	}
}

if (!spl_autoload_register('fwgv_autoload')) {
	header('Content-type: text/html; charset=UTF-8', true, 500);
	die('Internal System Error on Startup');
}

/**
 *  \brief Classe Exception extendida do FW
 */
class FW_Exception extends Exception {
    private $context = null;
    public function __construct($code, $message, $file, $line, $context = null) {
        parent::__construct($message, $code);
        $this->file = $file;
        $this->line = $line;
        $this->context = $context;
    }
	public function getContext() {
		return $this->context;
	}
};

/**
 *	\brief Função de tratamento de exceções
 */
function FW_ExceptionHandler($error) {
	Errors::errorHandler($error->getCode(), $error->getMessage(), $error->getFile(), $error->getLine(), (method_exists($error, 'getContext') ? $error->getContext() : null));
}
set_exception_handler('FW_ExceptionHandler');

/**
 *	\brief Função de tratamento de erros
 */
error_reporting(E_ALL);
function FW_ErrorHandler($errno, $errstr, $errfile, $errline, $errContext) {
	Errors::errorHandler($errno, $errstr, $errfile, $errline, $errContext);
}
set_error_handler('FW_ErrorHandler');

/*  ------------------------------------------------------------------------------------ --- -- -
	Início do script
	------------------------------------------------------------------------------------ --- -- - */

ob_start();

// Envia o content-type e o charset
header('Content-Type: text/html; charset='.$GLOBALS['SYSTEM']['CHARSET'], true);
// Envia o cache-control
header('Cache-Control: '.Configuration::get('system', 'cache-control'), true);

//ini_set('zlib.output_compression', 'on');
ini_set('mbstring.internal_encoding', $GLOBALS['SYSTEM']['CHARSET']);
ini_set('default_charset', $GLOBALS['SYSTEM']['CHARSET']);
ini_set('date.timezone', $GLOBALS['SYSTEM']['TIMEZONE']);

// Resolve a URI e monta as variáveis internas
$controller = URI::parseURI();

// Verifica se o acesso ao sistema necessita de autenticação
if (is_array(Configuration::get('system', 'authentication'))) {
	$auth = Configuration::get('system', 'authentication');
	if (isset($auth['user']) && isset($auth['pass'])) {
		if (!Cookie::get('__sys_auth__')) {
			if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_USER'] != $auth['user'] || $_SERVER['PHP_AUTH_PW'] != $auth['pass']) {
				header('WWW-Authenticate: Basic realm="' . utf8_decode('What are you doing here?') . '"');
				header('HTTP/1.0 401 Unauthorized');
				die('Não autorizado.');
			}
			Cookie::set('__sys_auth__', true);
		}
	}
}

// Verifica se o usuário desenvolvedor está acessando
if (Configuration::get('system', 'developer_user') && Configuration::get('system', 'developer_pass')) {
	if (URI::_GET( Configuration::get('system', 'developer_user') ) == Configuration::get('system', 'developer_pass')) {
		Cookie::set('_developer', true);
		/**
		 * A var $_SystemDeveloperOn é setada pois, quando o site tá em manutenção e é passado o user e pass para que o desenvolvedor veja o site,
		 * devido ao uso de Cookies, o site só aparece quando dá um refresh
		 */
		$_SystemDeveloperOn = true;
	} else if (URI::_GET( Configuration::get('system', 'developer_user') ) == 'off') {
		Cookie::delete('_developer');
	}
}

if (Cookie::exists('_developer') || isset($_SystemDeveloperOn)) {
	Configuration::set('system', 'maintenance', false);
	Configuration::set('system', 'debug', true);
}

// apenas se o debug estiver ligado, verifica se o DBA (modo de exibição de SQLs) está ligado
if (Configuration::get('system', 'debug') && Configuration::get('system', 'dba_user')) {
	if (URI::_GET( Configuration::get('system', 'dba_user') ) == Configuration::get('system', 'developer_pass')) {
		Cookie::set('_dba', true);
	} else if (URI::_GET( Configuration::get('system', 'dba_user') ) == 'off') {
		Cookie::delete('_dba');
	}

	if (Cookie::exists('_dba')) {
		Configuration::set('system', 'sql_debug', true);
	}
}

if (Configuration::get('system', 'debug')) {
	ini_set('display_errors', 1);
} else {
	ini_set('display_errors', 0);
}

// [pt-br] Verifica se o sistema está em manutenção
if (Configuration::get('system', 'maintenance')) {
	Errors::displayError(503, 'The system is under maintenance');
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
	// verifica se há uma classe default dentro da pasta
	$defaultFile = dirname($controller) . DIRECTORY_SEPARATOR . '_default.page.php';
	if (file_exists($defaultFile)) {
		require $defaultFile;
		new Default_Controller;
	}
	unset($defaultFile);

	// Valida a URI antes de carregar a controladora
	URI::validateURI();

	// Carrega a controladora
	require_once($controller);

	// Inicializa a controller
	$ControllerClassName = str_replace('-', '_', URI::getControllerClass()) . '_Controller';
	if (class_exists($ControllerClassName)) {
		$PageController = new $ControllerClassName;
		$PageMethod = str_replace('-', '_', URI::getSegment(0, true));

		if ($PageMethod && method_exists($PageController, $PageMethod)) {
			call_user_func(array($PageController, $PageMethod));
		} elseif (method_exists($PageController, '_default')) {
			call_user_func(array($PageController, '_default'));
		}
	} else {
		Errors::displayError(404, 'No ' . $ControllerClassName . ' on ' . $controller);
	}
	unset($controller);
} else {
	// [pr-br] Se a aplicação usa o mini CMS, carrega o artigo para a memória
	if ($GLOBALS['SYSTEM']['CMS'] && CMS::checkArticleOrCategory()) {
		$tpl = new Template;

		if (CMS::isArticleLoaded()) {
			CMS::loadArticleToTemplate();
		} elseif (CMS::isCategoryLoaded()) {
			if (($pg = URI::getSegment(0, true)) && is_numeric($pg)) {
				$pg = (int)URI::getSegment(0, true);
				if ($pg < 1) $pg = 1;
			} else {
				$pg = 1;
			}

			$articles_per_page = Configuration::get('cms', 'articles_per_page');

			CMS::loadCategoryToTemplate();
			CMS::loadArticlesToTemplate(($pg - 1) * $articles_per_page, $articles_per_page);
		}

		if (!$tpl->templateExists(URI::getControllerClass()) && $tpl->templateExists('_template')) {
			$tpl->setTemplate('_template');
		}
	} else {
		if ($Page = URI::getSegment(0, false)) {
			if ($Page == 'framework' || $Page == 'about' || $Page == 'copyright' || $Page == 'credits' || $Page == 'fval' || $Page == '_') {
				Kernel::printCopyright();
			} else if ($Page == '_pi_') {
				phpinfo();
				ob_end_flush();
				exit;
			} else if (in_array($Page, array('_system_bug_', '_system_bug_solved_'))) {
				// Verifica se o acesso ao sistema necessita de autenticação
				$auth = Configuration::get('system', 'bug_authentication');
				if (!empty($auth['user']) && !empty($auth['pass'])) {
					if (!Cookie::get('__sys_bug_auth__')) {
						if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_USER'] != $auth['user'] || $_SERVER['PHP_AUTH_PW'] != $auth['pass']) {
							header('WWW-Authenticate: Basic realm="' . utf8_decode('What r u doing here?') . '"');
							header('HTTP/1.0 401 Unauthorized');
							die('Não autorizado.');
						}
						Cookie::set('__sys_bug_auth__', true);
					}
				}

				if ($Page == '_system_bug_') {
					Errors::bugList();
				} else if ($Page == '_system_bug_solved_' && preg_match('/^[0-9a-z]{8}$/', URI::getSegment(1, false))) {
					Errors::bugSolved(URI::getSegment(1, false));
				}
			}
		}

		// Nenhuma controller definida e não está usando CMS ou não há artigo correspondente
		Errors::displayError(404, URI::relativePathPage() . '/' . URI::currentPage());
	}
}

// se o template estiver carregado, imprime
if (isset($tpl)) {
	$tpl->display();
}

// se tiver algum debug, utiliza-o
Kernel::debugPrint();

ob_end_flush();