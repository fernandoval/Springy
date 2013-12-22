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
		0.2.0

	This script:
		Script de login da área de administração do CMS
	------------------------------------------------------------------------------------ --- -- - */

class Login_Controller {
	function __construct() {
		if (FW\CMS::logged_in_user()) {
			FW\URI::redirect('/'.FW\URI::relativePathPage());
			return false;
		}

		$error = 0;
		$user = isset($_POST['user']) ? trim($_POST['user']) : '';
		$password = isset($_POST['password']) ? trim($_POST['password']) : '';
		if (!empty($user) && !empty($password)) {
			if (FW\CMS::login_user($user, $password)) {
				FW\URI::redirect('/'.FW\URI::relativePathPage());
				return false;
			} else {
				$error = 1;
			}
		}

		FW\Template::start();
		FW\Template::assign('CMS_LoginError', $error);
	}
}
?>