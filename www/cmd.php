<?php
/** \file
 *  FVAL PHP Framework for Web Applications
 *  
 *	\copyright Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 *	\copyright Copyright (c) 2007-2013 Fernando Val\n
 *	\copyright Copyright (c) 2009-2013 Lucas Cardozo
 *
 *	\brief Script de execução via shell para crontab
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version 1.2.3
 *  \author		Fernando Val  - fernando.val@gmail.com
 *  \ingroup framework
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
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = $argv[1];
$_SERVER['SERVER_PROTOCOL'] = 'CLI/Mode';
$_SERVER['HTTP_HOST'] = 'cmd.shell';
$_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__);

if (!empty($argv[2])) {
	$_SERVER['REQUEST_URI']  .= '?' . $argv[2];
	$_SERVER['QUERY_STRING'] .= '&' . $argv[2];
	
	foreach (explode('&', $argv[2]) as $get) {
		$get = explode('=', $get);
		$_GET[ $get[0] ] = $get[1];
	}
}

if (!empty($argv[3])) {
	$_SERVER['HTTP_HOST'] = $argv[3];
}

require_once '_Main.php';
