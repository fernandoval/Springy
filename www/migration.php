#!/usr/bin/php
<?php

/**
 * Command line migration script launcher.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version 0.4.2
 */

if (!file_exists('sysconf.php')) {
    echo 'Internal System Error on Startup.',"\n";
    echo 'Required file "sysconf.php" missing.',"\n";
    exit(999);
}
if (!file_exists('_Main.php')) {
    echo 'Internal System Error on Startup.',"\n";
    echo 'Required file "_Main.php" missing.',"\n";
    exit(999);
}

if (!defined('STDIN') || empty($argc)) {
    echo 'This script can be executed only in CLI mode.';
    exit(998);
}

$_SERVER['QUERY_STRING'] = '';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '__migration__';
$_SERVER['SERVER_PROTOCOL'] = 'CLI/Mode';
$_SERVER['HTTP_HOST'] = 'cmd.shell';
$_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__);

require_once '_Main.php';
