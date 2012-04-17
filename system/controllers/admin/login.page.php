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
		if (CMS::logged_in_user()) {
			URI::redirect('/'.URI::relative_path_page());
			return false;
		}

		$error = 0;
		$user = isset($_POST['user']) ? trim($_POST['user']) : '';
		$password = isset($_POST['password']) ? trim($_POST['password']) : '';
		if (!empty($user) && !empty($password)) {
			if (CMS::login_user($user, $password)) {
				URI::redirect('/'.URI::relative_path_page());
				return false;
			} else {
				$error = 1;
			}
		}

		Template::start();
		Template::assign('CMS_LoginError', $error);
	}
}
?>