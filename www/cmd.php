<?php
/** \file
 *  FVAL PHP Framework for Web Applications
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \version 1.0.0
 *
 *	\brief Script de execução via shell para crontab
 */

if (!defined('STDIN') || empty($argc)) {
	echo 'This script is runnable only on CLI mode.';
	exit(998);
}
 
if ($argc < 2) {
	echo 'Command missing.';
	exit(999);
}

$_GET['SUPERVAR'] = $argv[1];
$_SERVER['QUERY_STRING'] = 'SUPERVAR=' . $argv[1];

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = $argv[1];
$_SERVER['SERVER_PROTOCOL'] = 'CLI/Mode';
$_SERVER['HTTP_HOST'] = 'cmd.shell';

if (!empty($argv[2])) {
	$_SERVER['REQUEST_URI']  .= '?' . $argv[2];
	$_SERVER['QUERY_STRING'] .= '&' . $argv[2];
	$_GET = array_merge($_GET, explode('&', $argv[2]));
}

require_once 'index.php';
