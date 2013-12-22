<?php
/*  ------------------------------------------------------------------------------------ --- -- -
	FVAL PHP Framework for Web Sites

	Copyright (c) 2007-2009 FVAL - Consultoria e Informática Ltda.
	Copyright (C) 2009 Fernando Val

	http://www.fval.com.br

	Developer team:
		Fernando Val  - fernando.val@gmail.com

	Framework version:
		1.0.0

	Script version:
		0.1.1

	This script:
		Script de logout da área de administração do CMS
	------------------------------------------------------------------------------------ --- -- - */

class Logout_Controller {
	function __construct() {
		if (!FW\CMS::logged_in_user()) {
			FW\URI::redirect('/'.FW\URI::relativePathPage().'/login');
			return false;
		}

		if (FW\CMS::logout_user()) {
			FW\URI::redirect('/'.FW\URI::relativePathPage().'/login');
			return true;
		}

		FW\Errors::displayError(500, 'Can not logout');
	}
}
?>