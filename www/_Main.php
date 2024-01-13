<?php

/**
 * Springy web launcher script.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version    5.3.0
 */

$springyStartTime = microtime(true); // Memoriza a hora do início do processamento

// Kill system with internal error 500 if initial setup file does not exists
if (!file_exists('sysconf.php') || !file_exists('helpers.php')) {
    header('Content-type: text/html; charset=UTF-8', true, 500);
    echo('Internal System Error on Startup');
    exit(1);
}

// Load framework configuration
$sysconf = require_once 'sysconf.php';

if (!isset($sysconf['ROOT_PATH'])) {
    header('Content-type: text/html; charset=UTF-8', true, 500);
    echo('Web server document root configuration not found.');
    exit(1);
}

// Load helper script.
require 'helpers.php';

// Define error handlers
error_reporting(E_ALL);
set_exception_handler('springyExceptionHandler');
set_error_handler('springyErrorHandler');

// Load Composer autoload
$autoloadFile = implode(
    DS,
    [$sysconf['VENDOR_PATH'] ?? implode(DS, ['..', 'vendor']), 'autoload.php',]
);
if (file_exists($autoloadFile)) {
    require $autoloadFile;
}

// Kill system with internal error 500 if can not set autoload funcion
// @deprecated 4.5.0
// if (!spl_autoload_register('springyAutoload', true, true)) {
//     header('Content-type: text/html; charset=UTF-8', true, 500);
//     exit('Internal System Error on Startup');
// }

// System start
ob_start();
Springy\Kernel::run($sysconf, $springyStartTime);

if (count(ob_list_handlers())) {
    ob_end_flush();
}
