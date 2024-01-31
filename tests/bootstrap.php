<?php

/**
 * Autoload initialization script for PHPUnit.
 *
 * @copyright 2015 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   1.1.0
 */

define('SPRINGY_START', microtime(true));

require __DIR__ . '/../consts';
// Loads the Composer autoload
require __DIR__ . '/../vendor/autoload.php';

restore_exception_handler();
restore_error_handler();

// Springy\Kernel::initiate($GLOBALS['SYSTEM'], $springyStartTime);
