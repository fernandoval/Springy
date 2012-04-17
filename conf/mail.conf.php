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

	Script version:s
		1.0.0

	This script:
		Configurations for mail class
		
	Configuration keys:
		method - The method user to send messagens. See avaliable methos below.
			smtp     = Send thru a SMTP connection
			sendmail = Send using Sendmail daemon server
			default  = Send via PHP mail (default)
	------------------------------------------------------------------------------------ --- -- - */

/* *************** CONFIGURAÇÕES DE DESENVOLVIMENTO *************** */

$conf['development']['method'] = 'default';
$conf['development']['host'] = 'localhost';
$conf['development']['port'] = '25';
$conf['development']['ssl'] = '0';
$conf['development']['starttls'] = '0';
$conf['development']['direct_delivery'] = '0';
$conf['development']['exclude_address'] = '';
$conf['development']['user'] = '';
$conf['development']['realm'] = '';
$conf['development']['workstation'] = '';
$conf['development']['pass'] = '';
$conf['development']['exclude_address'] = '';
$conf['development']['auth_host'] = '';
$conf['development']['debug'] = 0;
$conf['development']['html_debug'] = 1;
$conf['development']['errors_go_to'] = 'yourname@yourisp.com';


/* *************** CONFIGURAÇÕES DE PRODUCAO *************** */

$conf['production']['method'] = 'default';
$conf['production']['host'] = 'localhost';
$conf['production']['port'] = '25';
$conf['production']['ssl'] = '0';
$conf['production']['starttls'] = '0';
$conf['production']['direct_delivery'] = '0';
$conf['production']['exclude_address'] = '';
$conf['production']['user'] = '';
$conf['production']['realm'] = '';
$conf['production']['workstation'] = '';
$conf['production']['pass'] = '';
$conf['production']['exclude_address'] = '';
$conf['production']['auth_host'] = '';
$conf['production']['debug'] = 0;
$conf['production']['hlmt_debug'] = 0;
$conf['production']['errors_go_to'] = 'yourname@yourisp.com';

?>