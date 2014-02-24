<?php
/** \file
 *  FVAL PHP Framework for Web Applications
 *  
 *	\copyright Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 *	\copyright Copyright (c) 2007-2013 Fernando Val\n
 *	\copyright Copyright (c) 2009-2013 Lucas Cardozo
 *
 *  http://www.fval.com.br
 *
 *	\brief		Script de inicialização da aplicação
 *  \version	2.2.15
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
 *  \defgroup app_classes Classes da aplicação
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
function fwgv_autoload($classe)
{
	$aclass = explode('\\', $classe);
	if (count($aclass) > 1) {
		if ($aclass[0] == 'FW') {
			array_shift($aclass);
		}
		$file = implode(DIRECTORY_SEPARATOR, $aclass);
	} else {
		$file = $aclass[0];
	}
	
	if (file_exists($GLOBALS['SYSTEM']['LIBRARY_PATH'] . DIRECTORY_SEPARATOR . $file . '.php')) {
		require_once($GLOBALS['SYSTEM']['LIBRARY_PATH'] . DIRECTORY_SEPARATOR . $file . '.php');
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
		if (file_exists($GLOBALS['SYSTEM']['CLASS_PATH'] . DIRECTORY_SEPARATOR . $nameSpace . DIRECTORY_SEPARATOR . $classe. '.php')) {
			require_once $GLOBALS['SYSTEM']['CLASS_PATH'] . DIRECTORY_SEPARATOR . $nameSpace . DIRECTORY_SEPARATOR . $classe . '.php';
		} elseif (file_exists($GLOBALS['SYSTEM']['CLASS_PATH'] . DIRECTORY_SEPARATOR . $classe . '.php')) {
			require_once $GLOBALS['SYSTEM']['CLASS_PATH'] . DIRECTORY_SEPARATOR . $classe . '.php';
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
class FW_Exception extends Exception
{
	/// Contexto do erro
    private $context = null;
	
    public function __construct($code, $message, $file, $line, $context = null)
	{
        parent::__construct($message, $code);
        $this->file = $file;
        $this->line = $line;
        $this->context = $context;
    }
	
	/**
	 *  \brief Pega o contexto do erro
	 */
	public function getContext()
	{
		return $this->context;
	}
};

/**
 *	\brief Função de tratamento de exceções
 */
function FW_ExceptionHandler($error)
{
	FW\Errors::errorHandler($error->getCode(), $error->getMessage(), $error->getFile(), $error->getLine(), (method_exists($error, 'getContext') ? $error->getContext() : null));
}
set_exception_handler('FW_ExceptionHandler');

/**
 *	\brief Função de tratamento de erros
 */
error_reporting(E_ALL);
function FW_ErrorHandler($errno, $errstr, $errfile, $errline, $errContext)
{
	FW\Errors::errorHandler($errno, $errstr, $errfile, $errline, $errContext);
}
set_error_handler('FW_ErrorHandler');

/*  ------------------------------------------------------------------------------------ --- -- -
	Início do script
	------------------------------------------------------------------------------------ --- -- - */

ob_start();

// Envia o content-type e o charset
header('Content-Type: text/html; charset='.$GLOBALS['SYSTEM']['CHARSET'], true);
// Envia o cache-control
header('Cache-Control: '.FW\Configuration::get('system', 'cache-control'), true);

//ini_set('zlib.output_compression', 'on');
ini_set('mbstring.internal_encoding', $GLOBALS['SYSTEM']['CHARSET']);
ini_set('default_charset', $GLOBALS['SYSTEM']['CHARSET']);
ini_set('date.timezone', $GLOBALS['SYSTEM']['TIMEZONE']);

// Resolve a URI e monta as variáveis internas
$controller = FW\URI::parseURI();
	
// Verifica se o acesso ao sistema necessita de autenticação
if (is_array(FW\Configuration::get('system', 'authentication'))) {
	$auth = FW\Configuration::get('system', 'authentication');
	if (isset($auth['user']) && isset($auth['pass'])) {
		if (!FW\Cookie::get('__sys_auth__')) {
			if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_USER'] != $auth['user'] || $_SERVER['PHP_AUTH_PW'] != $auth['pass']) {
				header('WWW-Authenticate: Basic realm="' . utf8_decode('What are you doing here?') . '"');
				header('HTTP/1.0 401 Unauthorized');
				die('Não autorizado.');
			}
			FW\Cookie::set('__sys_auth__', true);
		}
	}
}

// Verifica se o usuário desenvolvedor está acessando
if (FW\Configuration::get('system', 'developer_user') && FW\Configuration::get('system', 'developer_pass')) {
	if (FW\URI::_GET( FW\Configuration::get('system', 'developer_user') ) == FW\Configuration::get('system', 'developer_pass')) {
		FW\Cookie::set('_developer', true);
		/**
		 * A var $_SystemDeveloperOn é setada pois, quando o site tá em manutenção e é passado o user e pass para que o desenvolvedor veja o site,
		 * devido ao uso de Cookies, o site só aparece quando dá um refresh
		 */
		$_SystemDeveloperOn = true;
	} else if (FW\URI::_GET( FW\Configuration::get('system', 'developer_user') ) == 'off') {
		FW\Cookie::delete('_developer');
	}
}

if (FW\Cookie::exists('_developer') || isset($_SystemDeveloperOn)) {
	FW\Configuration::set('system', 'maintenance', false);
	FW\Configuration::set('system', 'debug', true);
}

// apenas se o debug estiver ligado, verifica se o DBA (modo de exibição de SQLs) está ligado
if (FW\Configuration::get('system', 'debug') && FW\Configuration::get('system', 'dba_user')) {
	if (FW\URI::_GET( FW\Configuration::get('system', 'dba_user') ) == FW\Configuration::get('system', 'developer_pass')) {
		FW\Cookie::set('_dba', true);
	} else if (FW\URI::_GET( FW\Configuration::get('system', 'dba_user') ) == 'off') {
		FW\Cookie::delete('_dba');
	}

	if (FW\Cookie::exists('_dba')) {
		FW\Configuration::set('system', 'sql_debug', true);
	}
}

if (FW\Configuration::get('system', 'debug')) {
	ini_set('display_errors', 1);
} else {
	ini_set('display_errors', 0);
}

// [pt-br] Verifica se o sistema está em manutenção
if (FW\Configuration::get('system', 'maintenance')) {
	FW\Errors::displayError(503, 'The system is under maintenance');
}

// Se foi definido uma Controller, carrega para a memória
if (!is_null($controller)) {
	// Valida a URI antes de carregar a controladora
	FW\URI::validateURI();

	// Carrega a controladora
	require_once($controller);

	// Define o nome da classe controladora
	$ControllerClassName = str_replace('-', '_', FW\URI::getControllerClass()) . '_Controller';
}

// Se a classe controladora foi carregada e ela não possui sua própria classe Global, checa a existência da _global
if (!isset($ControllerClassName) || !method_exists($ControllerClassName, '_ignore_global')) {
	// Verifica se a controller _global existe
	$path = $GLOBALS['SYSTEM']['CONTROLER_PATH'] . DIRECTORY_SEPARATOR . '_global.php';
	if (file_exists($path)) {
		require_once($path);
		if (class_exists('Global_Controller')) {
			new Global_Controller;
		}
	}
	unset($path);
}

// Se foi definido uma Controller, carega
if (isset($ControllerClassName)) {
	// Verifica a existência da controladora hook dentro da pasta da controladora atual
	$defaultFile = dirname($controller) . DIRECTORY_SEPARATOR . '_default.php';
	if (file_exists($defaultFile)) {
		require $defaultFile;
		if (class_exists('Default_Controller')) {
			new Default_Controller;
		}
	}
	unset($defaultFile);

	// Inicializa a controller
	if (class_exists($ControllerClassName)) {
		$PageController = new $ControllerClassName;
		$PageMethod = str_replace('-', '_', FW\URI::getSegment(0, true));

		if ($PageMethod && method_exists($PageController, $PageMethod)) {
			call_user_func(array($PageController, $PageMethod));
		} elseif (method_exists($PageController, '_default')) {
			call_user_func(array($PageController, '_default'));
		}
	} else {
		FW\Errors::displayError(404, 'No ' . $ControllerClassName . ' on ' . $controller);
	}
	unset($controller);
} else {
	// [pr-br] Se a aplicação usa o mini CMS, carrega o artigo para a memória
	if ($GLOBALS['SYSTEM']['CMS'] && FW\CMS::checkArticleOrCategory()) {
		$tpl = new Template;

		if (FW\CMS::isArticleLoaded()) {
			FW\CMS::loadArticleToTemplate();
		} elseif (FW\CMS::isCategoryLoaded()) {
			if (($pg = FW\URI::getSegment(0, true)) && is_numeric($pg)) {
				$pg = (int)FW\URI::getSegment(0, true);
				if ($pg < 1) $pg = 1;
			} else {
				$pg = 1;
			}

			$articles_per_page = FW\Configuration::get('cms', 'articles_per_page');

			FW\CMS::loadCategoryToTemplate();
			FW\CMS::loadArticlesToTemplate(($pg - 1) * $articles_per_page, $articles_per_page);
		}

		if (!$tpl->templateExists(FW\URI::getControllerClass()) && $tpl->templateExists('_template')) {
			$tpl->setTemplate('_template');
		}
	} else {
		if ($Page = FW\URI::getSegment(0, false)) {
			if ($Page == 'framework' || $Page == 'about' || $Page == 'copyright' || $Page == 'credits' || $Page == 'fval' || $Page == '_') {
				FW\Kernel::printCopyright();
			} else if ($Page == '_pi_') {
				phpinfo();
				ob_end_flush();
				exit;
			} else if (in_array($Page, array('_system_bug_', '_system_bug_solved_'))) {
				// Verifica se o acesso ao sistema necessita de autenticação
				$auth = FW\Configuration::get('system', 'bug_authentication');
				if (!empty($auth['user']) && !empty($auth['pass'])) {
					if (!FW\Cookie::get('__sys_bug_auth__')) {
						if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_USER'] != $auth['user'] || $_SERVER['PHP_AUTH_PW'] != $auth['pass']) {
							header('WWW-Authenticate: Basic realm="' . utf8_decode('What r u doing here?') . '"');
							header('HTTP/1.0 401 Unauthorized');
							die('Não autorizado.');
						}
						FW\Cookie::set('__sys_bug_auth__', true);
					}
				}

				if ($Page == '_system_bug_') {
					FW\Errors::bugList();
				} else if ($Page == '_system_bug_solved_' && preg_match('/^[0-9a-z]{8}$/', FW\URI::getSegment(1, false))) {
					FW\Errors::bugSolved(FW\URI::getSegment(1, false));
				}
			}
		}

		// Nenhuma controller definida e não está usando CMS ou não há artigo correspondente
		FW\Errors::displayError(404, FW\URI::relativePathPage() . '/' . FW\URI::currentPage());
	}
}

// se o template estiver carregado, imprime
if (isset($tpl)) {
	$tpl->display();
}

// se tiver algum debug, utiliza-o
FW\Kernel::debugPrint();

ob_end_flush();