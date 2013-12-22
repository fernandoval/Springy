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
		0.1.0

	This script:
		Script index da área de administração do CMS
	------------------------------------------------------------------------------------ --- -- - */

class Index_Controller {
	function __construct() {
		if (!FW\CMS::logged_in_user()) {
			FW\URI::redirect('/' . FW\URI::relativePathPage() . '/login');

			return false;
		}

		FW\Template::start();
	}
}
?>