<?php
/**	@file
 *  Springy.
 *
 *  @brief     Autoload initialization script for PHPUnit.
 *
 *  @copyright Copyright (c) 2007-2018 Fernando Val
 *  @author    Fernando Val - fernando.val@gmail.com
 *
 *  @version   0.3.0.2
 *  @ingroup   tests
 */

// Edit the two lines above and set the relative path to sysconf.php e helpers.php scripts
define('SYSCONF', 'www/sysconf.php');
define('HELPERS', 'www/helpers.php');

require SYSCONF;
require HELPERS;
if (!spl_autoload_register('springyAutoload')) {
    die('Internal System Error on Startup');
}

// Load the Composer autoload script
if (file_exists(sysconf('VENDOR_PATH').DIRECTORY_SEPARATOR.'autoload.php')) {
    require sysconf('VENDOR_PATH').DIRECTORY_SEPARATOR.'autoload.php';
}
