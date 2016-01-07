<?php
/** \file
 *  FVAL PHP Framework for Web Applications.
 *
 *  \copyright Copyright ₢ 2007-2015 FVAL Consultoria e Informática Ltda.\n
 *  \copyright Copyright ₢ 2007-2015 Fernando Val\n
 *  \copyright Copyright ₢ 2009-2013 Lucas Cardozo
 *
 *  http://www.fval.com.br
 *
 *  \brief    System initialization script
 *  \version  4.1.25
 *  \author   Fernando Val - fernando.val@gmail.com
 *  \author   Lucas Cardozo - lucas.cardozo@gmail.com
 *
 *  \defgroup framework Biblioteca do Framework
 *
 *  @{
 *  @}
 *  \defgroup config Configurações do sistema
 *  @{
 *  @}
 *  \defgroup controllers Controladoras da aplicação
 *  @{
 *  @}
 *  \defgroup app_classes Classes da aplicação
 *  @{
 *  @}
 *  \defgroup templates Templates da aplicação
 *  @{
 *  @}
 *
 *  \ingroup framework
 */
$FWGV_START_TIME = microtime(true); // Memoriza a hora do início do processamento

// Kill system with internal error 500 if initial setup file does not exists
if (!file_exists('sysconf.php')) {
    header('Content-type: text/html; charset=UTF-8', true, 500);
    die('Internal System Error on Startup');
}
require 'sysconf.php';

/**
 * \brief Load helper script.
 */
require 'helpers.php';

// Kill system with internal error 500 if can not set autoload funcion
if (!spl_autoload_register('fwgv_autoload')) {
    header('Content-type: text/html; charset=UTF-8', true, 500);
    die('Internal System Error on Startup');
}

error_reporting(E_ALL);
set_exception_handler('FW_ExceptionHandler');
set_error_handler('FW_ErrorHandler');

/*
 *  \brief Carrega autoload do Composer, caso exista
 */
if (file_exists($GLOBALS['SYSTEM']['3RDPARTY_PATH'].DIRECTORY_SEPARATOR.'autoload.php')) {
    require $GLOBALS['SYSTEM']['3RDPARTY_PATH'].DIRECTORY_SEPARATOR.'autoload.php';
}

/*  ------------------------------------------------------------------------------------ --- -- -
    Início do script
    ------------------------------------------------------------------------------------ --- -- - */

ob_start();
FW\Kernel::initiate($GLOBALS['SYSTEM'], $FWGV_START_TIME);

// Envia o cache-control
header('Cache-Control: '.FW\Configuration::get('system', 'cache-control'), true);

// Resolve a URI e monta as variáveis internas
$controller = FW\URI::parseURI();

// Verifica se o acesso ao sistema necessita de autenticação
if (is_array(FW\Configuration::get('system', 'authentication'))) {
    $auth = FW\Configuration::get('system', 'authentication');
    if (isset($auth['user']) && isset($auth['pass'])) {
        if (!FW\Cookie::get('__sys_auth__')) {
            if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_USER'] != $auth['user'] || $_SERVER['PHP_AUTH_PW'] != $auth['pass']) {
                header('WWW-Authenticate: Basic realm="'.utf8_decode('What are you doing here?').'"');
                header('HTTP/1.0 401 Unauthorized');
                die('Não autorizado.');
            }
            FW\Cookie::set('__sys_auth__', true);
        }
    }
}

// Verifica se o usuário desenvolvedor está acessando
if (FW\Configuration::get('system', 'developer_user') && FW\Configuration::get('system', 'developer_pass')) {
    if (FW\URI::_GET(FW\Configuration::get('system', 'developer_user')) == FW\Configuration::get('system', 'developer_pass')) {
        FW\Cookie::set('_developer', true);
        /**
         * A var $_SystemDeveloperOn é setada pois, quando o site tá em manutenção e é passado o user e pass para que o desenvolvedor veja o site,
         * devido ao uso de Cookies, o site só aparece quando dá um refresh.
         */
        $_SystemDeveloperOn = true;
    } elseif (FW\URI::_GET(FW\Configuration::get('system', 'developer_user')) == 'off') {
        FW\Cookie::delete('_developer');
    }
}

if (FW\Cookie::exists('_developer') || isset($_SystemDeveloperOn)) {
    FW\Configuration::set('system', 'maintenance', false);
    FW\Configuration::set('system', 'debug', true);
}

// apenas se o debug estiver ligado, verifica se o DBA (modo de exibição de SQLs) está ligado
if (FW\Configuration::get('system', 'debug') && FW\Configuration::get('system', 'dba_user')) {
    if (FW\URI::_GET(FW\Configuration::get('system', 'dba_user')) == FW\Configuration::get('system', 'developer_pass')) {
        FW\Cookie::set('_dba', true);
    } elseif (FW\URI::_GET(FW\Configuration::get('system', 'dba_user')) == 'off') {
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
    require_once $controller;

    // Define o nome da classe controladora
    $ControllerClassName = str_replace('-', '_', FW\URI::getControllerClass()).'_Controller';
}

// Se a classe controladora foi carregada e ela não possui sua própria classe Global, checa a existência da _global
if (!defined('BYPASS_CONTROLLERS') && (!isset($ControllerClassName) || !method_exists($ControllerClassName, '_ignore_global'))) {
    // Verifica se a controller _global existe
    $path = FW\Kernel::path(FW\Kernel::PATH_CONTROLLER).DIRECTORY_SEPARATOR.'_global.php';
    if (file_exists($path)) {
        require_once $path;
        if (class_exists('Global_Controller')) {
            new Global_Controller();
        }
    }
    unset($path);
}

// Se foi definido uma Controller, carega
if (isset($ControllerClassName)) {
    // Verifica a existência da controladora hook dentro da pasta da controladora atual
    $defaultFile = dirname($controller).DIRECTORY_SEPARATOR.'_default.php';
    if (file_exists($defaultFile)) {
        require $defaultFile;
        if (class_exists('Default_Controller')) {
            new Default_Controller();
        }
    }
    unset($defaultFile);

    // Inicializa a controller
    if (class_exists($ControllerClassName)) {
        $PageController = new $ControllerClassName();
        $PageMethod = str_replace('-', '_', FW\URI::getSegment(0, true));

        if ($PageMethod && method_exists($PageController, $PageMethod)) {
            call_user_func([$PageController, $PageMethod]);
        } elseif (method_exists($PageController, '_default')) {
            call_user_func([$PageController, '_default']);
        }
    } else {
        FW\Errors::displayError(404, 'No '.$ControllerClassName.' on '.$controller);
    }
    unset($controller);
} else {
    if ($Page = FW\URI::getSegment(0, false)) {
        if ($Page == 'framework' || $Page == 'about' || $Page == 'copyright' || $Page == 'credits' || $Page == 'fval' || $Page == '_') {
            FW\Kernel::printCopyright();
        } elseif ($Page == '_pi_') {
            phpinfo();
            ob_end_flush();
            exit;
        } elseif ($Page == '_error_') {
            if ($error = FW\URI::getSegment(0)) {
                FW\Errors::displayError((int) $error, 'System error');
            }
        } elseif (in_array($Page, ['_system_bug_', '_system_bug_solved_'])) {
            // Verifica se o acesso ao sistema necessita de autenticação
            $auth = FW\Configuration::get('system', 'bug_authentication');
            if (!empty($auth['user']) && !empty($auth['pass'])) {
                if (!FW\Cookie::get('__sys_bug_auth__')) {
                    if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_USER'] != $auth['user'] || $_SERVER['PHP_AUTH_PW'] != $auth['pass']) {
                        header('WWW-Authenticate: Basic realm="'.utf8_decode('What r u doing here?').'"');
                        header('HTTP/1.0 401 Unauthorized');
                        die('Não autorizado.');
                    }
                    FW\Cookie::set('__sys_bug_auth__', true);
                }
            }

            if ($Page == '_system_bug_') {
                FW\Errors::bugList();
            } elseif ($Page == '_system_bug_solved_' && preg_match('/^[0-9a-z]{8}$/', FW\URI::getSegment(1, false))) {
                FW\Errors::bugSolved(FW\URI::getSegment(1, false));
            }
        } elseif ($Page == '__migration__') {
            // Cli mode only
            if (!defined('STDIN') || empty($argc)) {
                echo 'This script can be executed only in CLI mode.';
                exit(998);
            }

            require $GLOBALS['SYSTEM']['MIGRATION_PATH'].DS.'app'.DS.'migrator.php';
            $PageController = new FW\Migrator();
            $PageController->run();
        }
    }

    // Nenhuma controller definida e não está usando CMS ou não há artigo correspondente
    FW\Errors::displayError(404, FW\URI::relativePathPage().'/'.FW\URI::currentPage());
}

// se o template estiver carregado, imprime
if (isset($tpl)) {
    $tpl->display();
}

// se tiver algum debug, utiliza-o
FW\Kernel::debugPrint();

ob_end_flush();
