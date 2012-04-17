<?php
/*  ------------------------------------------------------------------------------------ --- -- -
	FVAL PHP Framework for Web Sites

	Copyright (c) 2007-2009 FVAL - Consultoria e Informática Ltda.
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
		Samples controller
	------------------------------------------------------------------------------------ --- -- - */

class Index_Controller {
	function __construct() {
		date_default_timezone_set('America/Sao_Paulo');
		$date = date('d/m/Y');
	
		Template::start();

		Template::assign('date', $date);
	}
}
?>