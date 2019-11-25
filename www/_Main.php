<?php
/**
 * Springy web launcher script.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version    5.0.1.34
 */
$springyStartTime = microtime(true); // Memoriza a hora do início do processamento

// Kill system with internal error 500 if initial setup file does not exists
if (!file_exists('sysconf.php') || !file_exists('helpers.php')) {
    header('Content-type: text/html; charset=UTF-8', true, 500);
    die('Internal System Error on Startup');
}

// Load framework configuration
require 'sysconf.php';
// Load helper script.
require 'helpers.php';

// Load Composer autoload
if (file_exists(sysconf('VENDOR_PATH') . DS . 'autoload.php')) {
    require sysconf('VENDOR_PATH') . DS . 'autoload.php';
}

// System start
ob_start();
Springy\Kernel::initiate($GLOBALS['SYSTEM'], $springyStartTime);

if (count(ob_list_handlers())) {
    ob_end_flush();
}
