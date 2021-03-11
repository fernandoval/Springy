<?php

/**
 * Autoload initialization script for PHPUnit.
 *
 * @copyright 2015 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   1.0.4
 */

// Edit the two lines above and set the relative path to sysconf.php e helpers.php scripts
define('SYSCONF', 'www/sysconf.php');
define('HELPERS', 'www/helpers.php');

require SYSCONF;
require HELPERS;
if (!spl_autoload_register('springyAutoload')) {
    exit('Internal System Error on Startup');
}

// Load the Composer autoload script
if (file_exists(sysconf('VENDOR_PATH') . DIRECTORY_SEPARATOR . 'autoload.php')) {
    require sysconf('VENDOR_PATH') . DIRECTORY_SEPARATOR . 'autoload.php';
}

// set_exception_handler('springyExceptionHandler');
restore_exception_handler();
// set_error_handler('springyErrorHandler');
restore_error_handler();

// Springy\Kernel::initiate($GLOBALS['SYSTEM'], $springyStartTime);
