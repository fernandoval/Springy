<?php
/** @file
 *  Springy.
 *
 *  @brief      System initialization script.
 *
 *  @copyright  ₢ 2007-2018 Fernando Val
 *  @author     Fernando Val - fernando.val@gmail.com
 *
 *  @version    5.0.0.33
 *
 *  @defgroup framework Framework library
 *  @{
 *  @}
 *  @defgroup config Configurations
 *  @{
 *  @}
 *  @defgroup controllers Application controllers
 *  @{
 *  @}
 *  @defgroup app_classes Application classes and models
 *  @{
 *  @}
 *  @defgroup templates Template files
 *  @{
 *  @}
 *
 *  @ingroup framework
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
if (file_exists($GLOBALS['SYSTEM']['VENDOR_PATH'].DIRECTORY_SEPARATOR.'autoload.php')) {
    require $GLOBALS['SYSTEM']['VENDOR_PATH'].DIRECTORY_SEPARATOR.'autoload.php';
}

// System start
ob_start();
Springy\Kernel::initiate($GLOBALS['SYSTEM'], $springyStartTime);

if (count(ob_list_handlers())) {
    ob_end_flush();
}
