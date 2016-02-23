<?php
/** \file
 *  Springy.
 *
 *  \brief      System initialization script
 *  \copyright  Copyright ₢ 2007-2016 Fernando Val
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \version    4.2.29
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
$springyStartTime = microtime(true); // Memoriza a hora do início do processamento

// Kill system with internal error 500 if initial setup file does not exists
if (!file_exists('sysconf.php') || !file_exists('helpers.php')) {
    header('Content-type: text/html; charset=UTF-8', true, 500);
    die('Internal System Error on Startup');
}
require 'sysconf.php';

/**
 * \brief Load helper script.
 */
require 'helpers.php';

// Kill system with internal error 500 if can not set autoload funcion
if (!spl_autoload_register('springyAutoload')) {
    header('Content-type: text/html; charset=UTF-8', true, 500);
    die('Internal System Error on Startup');
}

error_reporting(E_ALL);
set_exception_handler('springyExceptionHandler');
set_error_handler('springyErrorHandler');

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
Springy\Kernel::initiate($GLOBALS['SYSTEM'], $springyStartTime);

// Envia o cache-control
header('Cache-Control: '.Springy\Configuration::get('system', 'cache-control'), true);

// Resolve a URI e monta as variáveis internas
$controller = Springy\URI::parseURI();

// Verifica se o acesso ao sistema necessita de autenticação
if (is_array(Springy\Configuration::get('system', 'authentication'))) {
    $auth = Springy\Configuration::get('system', 'authentication');
    if (isset($auth['user']) && isset($auth['pass'])) {
        if (!Springy\Cookie::get('__sys_auth__')) {
            if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_USER'] != $auth['user'] || $_SERVER['PHP_AUTH_PW'] != $auth['pass']) {
                header('WWW-Authenticate: Basic realm="'.utf8_decode('What are you doing here?').'"');
                header('HTTP/1.0 401 Unauthorized');
                die('Não autorizado.');
            }
            Springy\Cookie::set('__sys_auth__', true);
        }
    }
}

// Verifica se o usuário desenvolvedor está acessando
if (Springy\Configuration::get('system', 'developer_user') && Springy\Configuration::get('system', 'developer_pass')) {
    if (Springy\URI::_GET(Springy\Configuration::get('system', 'developer_user')) == Springy\Configuration::get('system', 'developer_pass')) {
        Springy\Cookie::set('_developer', true);
        /**
         * A var $_SystemDeveloperOn é setada pois, quando o site tá em manutenção e é passado o user e pass para que o desenvolvedor veja o site,
         * devido ao uso de Cookies, o site só aparece quando dá um refresh.
         */
        $_SystemDeveloperOn = true;
    } elseif (Springy\URI::_GET(Springy\Configuration::get('system', 'developer_user')) == 'off') {
        Springy\Cookie::delete('_developer');
    }
}

if (Springy\Cookie::exists('_developer') || isset($_SystemDeveloperOn)) {
    Springy\Configuration::set('system', 'maintenance', false);
    Springy\Configuration::set('system', 'debug', true);
}

// apenas se o debug estiver ligado, verifica se o DBA (modo de exibição de SQLs) está ligado
if (Springy\Configuration::get('system', 'debug') && Springy\Configuration::get('system', 'dba_user')) {
    if (Springy\URI::_GET(Springy\Configuration::get('system', 'dba_user')) == Springy\Configuration::get('system', 'developer_pass')) {
        Springy\Cookie::set('_dba', true);
    } elseif (Springy\URI::_GET(Springy\Configuration::get('system', 'dba_user')) == 'off') {
        Springy\Cookie::delete('_dba');
    }

    if (Springy\Cookie::exists('_dba')) {
        Springy\Configuration::set('system', 'sql_debug', true);
    }
}

if (Springy\Configuration::get('system', 'debug')) {
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
}

// [pt-br] Verifica se o sistema está em manutenção
if (Springy\Configuration::get('system', 'maintenance')) {
    Springy\Errors::displayError(503, 'The system is under maintenance');
}

// Se foi definido uma Controller, carrega para a memória
if (!is_null($controller)) {
    // Valida a URI antes de carregar a controladora
    Springy\URI::validateURI();

    // Carrega a controladora
    require_once $controller;

    // Define o nome da classe controladora
    $ControllerClassName = str_replace('-', '_', Springy\URI::getControllerClass()).'_Controller';
}

// Se a classe controladora foi carregada e ela não possui sua própria classe Global, checa a existência da _global
if (!defined('BYPASS_CONTROLLERS') && (!isset($ControllerClassName) || !method_exists($ControllerClassName, '_ignore_global'))) {
    // Verifica se a controller _global existe
    $path = Springy\Kernel::path(Springy\Kernel::PATH_CONTROLLER).DIRECTORY_SEPARATOR.'_global.php';
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
        $PageMethod = str_replace('-', '_', Springy\URI::getSegment(0, true));

        if ($PageMethod && method_exists($PageController, $PageMethod)) {
            call_user_func([$PageController, $PageMethod]);
        } elseif (method_exists($PageController, '_default')) {
            call_user_func([$PageController, '_default']);
        }
    } else {
        Springy\Errors::displayError(404, 'No '.$ControllerClassName.' on '.$controller);
    }
    unset($controller);

    // se tiver algum debug, utiliza-o
    Springy\Kernel::debugPrint();
} else {
    $Page = Springy\URI::getSegment(0, false);
    if ($Page == '_springy_' || $Page == '_') {
        Springy\Kernel::printCopyright();
    } elseif ($Page == '_pi_') {
        phpinfo();
        ob_end_flush();
        exit;
    } elseif ($Page == '_error_') {
        if ($error = Springy\URI::getSegment(0)) {
            Springy\Errors::displayError((int) $error, 'System error');
        } else {
            Springy\Errors::displayError(404, 'Page not found');
        }
    } elseif (in_array($Page, ['_system_bug_', '_system_bug_solved_'])) {
        // Verifica se o acesso ao sistema necessita de autenticação
        $auth = Springy\Configuration::get('system', 'bug_authentication');
        if (!empty($auth['user']) && !empty($auth['pass'])) {
            if (!Springy\Cookie::get('__sys_bug_auth__')) {
                if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_USER'] != $auth['user'] || $_SERVER['PHP_AUTH_PW'] != $auth['pass']) {
                    header('WWW-Authenticate: Basic realm="'.utf8_decode('What r u doing here?').'"');
                    // header('HTTP/1.0 401 Unauthorized');
                    // die('Unauthorized!');
                    Springy\Errors::displayError(401, 'Unauthorized');
                }
                Springy\Cookie::set('__sys_bug_auth__', true);
            }
        }

        if ($Page == '_system_bug_') {
            Springy\Errors::bugList();
        } elseif ($Page == '_system_bug_solved_' && preg_match('/^[0-9a-z]{8}$/', Springy\URI::getSegment(1, false))) {
            Springy\Errors::bugSolved(Springy\URI::getSegment(1, false));
        } else {
            Springy\Errors::displayError(404, 'Page not found');
        }
    } elseif ($Page == '__migration__') {
        // Cli mode only
        if (!defined('STDIN') || empty($argc)) {
            echo 'This script can be executed only in CLI mode.';
            exit(998);
        }

        require $GLOBALS['SYSTEM']['MIGRATION_PATH'].DS.'app'.DS.'migrator.php';
        $PageController = new Springy\Migrator();
        $PageController->run();
    } else {
        Springy\Errors::displayError(404, 'Page not found');
    }
}

ob_end_flush();
