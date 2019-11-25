#!/usr/bin/php
<?php
/**
 * Command line launcher.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version 2.0.1.11
 */
if (!file_exists('sysconf.php')) {
    echo 'Internal System Error on Startup.',"\n";
    echo 'Required file "sysconf.php" missing.',"\n";
}
if (!file_exists('_Main.php')) {
    echo 'Internal System Error on Startup.',"\n";
    echo 'Required file "_Main.php" missing.',"\n";
}

if (!defined('STDIN') || empty($argc)) {
    echo 'This script can be executed only in CLI mode.';
    exit(998);
}

if ($argc < 2) {
    require 'sysconf.php';

    echo sysconf('SYSTEM_NAME') . ' v' . sysconf('SYSTEM_VERSION') . "\n";
    echo "\n";
    echo 'ERROR: Controller command missing.',"\n";
    echo "\n";
    echo 'Syntax:',"\n";
    echo '$ php -f cmd.php <controller> [--query_string <uri_string>] [--http_host <host_name>] [args...]',"\n";
    echo "\n";
    exit(999);
}

$_SERVER['QUERY_STRING'] = '';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = $argv[1];
$_SERVER['SERVER_PROTOCOL'] = 'CLI/Mode';
$_SERVER['HTTP_HOST'] = 'cmd.shell';
$_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__);

$arg = 1;
while (++$arg < $argc) {
    if ($argv[$arg] == '--query_string') {
        $arg += 1;
        if (isset($argv[$arg])) {
            $_SERVER['REQUEST_URI'] .= '?' . $argv[$arg];
            $_SERVER['QUERY_STRING'] .= $argv[$arg];

            foreach (explode('&', $argv[$arg]) as $get) {
                $get = explode('=', $get);
                $_GET[$get[0]] = $get[1];
                unset($get);
            }
        }
    } elseif ($argv[$arg] == '--http_host') {
        $arg += 1;
        if (isset($argv[$arg])) {
            $_SERVER['HTTP_HOST'] = $argv[$arg];
        }
    }
}
$_SERVER['QUERY_STRING'] = trim($_SERVER['QUERY_STRING'], '&');

require_once '_Main.php';
