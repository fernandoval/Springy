#!/usr/bin/env php
<?php

/*
 * Command line launcher.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version 3.0.0
 *
 * @codingStandardsIgnoreFile
 */

define('SPRINGY_START', microtime(true));

require __DIR__ . '/consts';
// Loads the Composer autoload
require __DIR__ . '/vendor/autoload.php';

if ($argc < 2) {
    echo app_name() . ' v' . app_version() . "\n";
    echo "\n";
    echo 'ERROR: Controller command missing.',"\n";
    echo "\n";
    echo 'Syntax:',"\n";
    echo '$ php cmd <controller> [--query_string <uri_string>] [--http_host <host_name>] [args...]',"\n";
    echo "\n";
    exit(999);
}

// Load framework configuration
$sysconf = file_exists(web_root() . '/sysconf.php') ? require_once web_root() . '/sysconf.php' : [];

$_SERVER['QUERY_STRING'] = '';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = $argv[1];
$_SERVER['SERVER_PROTOCOL'] = 'CLI/Mode';
$_SERVER['HTTP_HOST'] = 'cmd.shell';
$_SERVER['DOCUMENT_ROOT'] = __DIR__;

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

    $_SERVER['QUERY_STRING'] = trim($_SERVER['QUERY_STRING'], '&');
}

ob_start();
Springy\Kernel::run($sysconf);
