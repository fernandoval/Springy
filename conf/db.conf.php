<?php
/*  ------------------------------------------------------------------------------------ --- -- -
	FVAL PHP Framework for Web Sites

	Copyright (C) 2009 FVAL - Consultoria e Informática Ltda.
	Copyright (C) 2009 Fernando Val
	Copyright (C) 2009 Lucas Cardozo

	http://www.fval.com.br

	Developer team:
		Fernando Val  - fernando.val@gmail.com
		Lucas Cardozo - lucas.cardozo@gmail.com

	Framework version:
		1.0.0

	Script version:
		1.0.0

	This script:
		Database configurations
		
	Configuration keys:
		database_type - The database server type. See below valid data.
			MySQL      = mysql
			PostgreSQL = pgsql or postgresql
			SQLite     = sqlite
	------------------------------------------------------------------------------------ --- -- - */

/* *************** CONFIGURAÇÕES DE DESENVOLVIMENTO *************** */

$conf['development']['database_type'] = 'mysql';
$conf['development']['host_name']     = '';
$conf['development']['user_name']     = '';
$conf['development']['password']      = '';
$conf['development']['database']      = '';
$conf['development']['persistent']    = false;


/* *************** CONFIGURAÇÕES DE PRODUCAO *************** */

$conf['production']['database_type'] = 'mysql';
$conf['production']['host_name']     = '';
$conf['production']['user_name']     = '';
$conf['production']['password']      = '';
$conf['production']['database']      = '';
$conf['production']['persistent']    = true;

?>