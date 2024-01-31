<?php // phpcs:ignore

/*
 * Springy web launcher script.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   6.0.0
 */

define('SPRINGY_START', microtime(true));

require __DIR__ . '/../consts';
// Loads the Composer autoload
require __DIR__ . '/../vendor/autoload.php';

// Load framework configuration
$sysconf = file_exists('sysconf.php') ? require_once 'sysconf.php' : [];

// Define error handlers
error_reporting(E_ALL);
set_exception_handler('springyExceptionHandler');
set_error_handler('springyErrorHandler');

// System start
ob_start();
Springy\Kernel::run($sysconf);

if (count(ob_list_handlers())) {
    ob_end_flush();
}
