#!/usr/bin/php
<?php
/** \file
 *  FVAL PHP Framework for Web Applications
 *  
 *  \copyright Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 *  \copyright Copyright (c) 2007-2015 Fernando Val
 *
 *	\brief   This is a database migration script
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version 0.1
 *  \author  Fernando Val  - fernando.val@gmail.com
 *  \ingroup framework
 */

if (!file_exists(__DIR__ . '/sysconf.php')) {
	echo 'Internal System Error on Startup.',"\n";
	echo 'Required file "sysconf.php" missing.',"\n";
	exit(999);
}
if (!file_exists(__DIR__ . '/_Main.php')) {
	echo 'Internal System Error on Startup.',"\n";
	echo 'Required file "_Main.php" missing.',"\n";
	exit(999);
}

if (!defined('STDIN') || empty($argc)) {
	echo 'This script can be executed only in CLI mode.';
	exit(998);
}

$_GET['SUPERVAR'] = '__migration__';
$_SERVER['QUERY_STRING'] = 'SUPERVAR=__migration__';

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '__migration__';
$_SERVER['SERVER_PROTOCOL'] = 'CLI/Mode';
$_SERVER['HTTP_HOST'] = 'cmd.shell';
$_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__);

require_once '_Main.php';
