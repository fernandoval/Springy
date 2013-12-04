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
		0.1.3

	This script:
		Script de administração de categorias
	------------------------------------------------------------------------------------ --- -- - */

class Categories_Controller {
	function __construct() {
		if (!CMS::logged_in_user()) {
			URI::redirect('/'.URI::relativePathPage().'/login');
			return false;
		}

		Template::start();
	}

	function _default() {
		if (($pg = URI::getSegment(0, true)) && is_numeric($pg)) {
			$pg = (int)URI::getSegment(0, true);
			if ($pg < 1) $pg = 1;
		} else {
			$pg = 1;
		}

		if ($articles = CMS::getCategories(false, 'title', 'ASC', ($pg - 1) * 20, 20, $qtde)) {
			Template::assign('CMS_CategoryList', $articles);

			$pagination = new Pagination;
			$pagination->setSiteLink('/'.URI::relativePathPage().'/categories/[page]');
			$pagination->CalculateNumPages($qtde, 20);
			Template::assign('Pagination', $pagination->parse($pg));
		}
	}

	function edit() {
		if (($id = URI::getSegment(1, true)) && is_numeric($id) && ($category = CMS::getCategoryById($id, false))) {
			if (!empty($_POST)) {
				if (empty($_POST['published'])) $_POST['published'] = 0;
				if (CMS::updateCategory($id, $_POST, $error) !== false) {
					URI::redirect('/'.URI::relativePathPage().'/categories');
					return false;
				}
				die('error: '.$error);
			}
			Template::assign('CMS_Category', $category);
		} else {
			Errors::displayError(404, 'Not found');
		}
	}

	function add() {
		if (!empty($_POST)) {
			if (CMS::insertCategory($_POST, $error) !== false) {
				URI::redirect('/'.URI::relativePathPage().'/categories');
				return false;
			}
			die('error: '.$error);
		} else {
			$category = array('category_id' => 0, 'published' => '1');
		}
		Template::assign('CMS_Category', $category);
	}

	function delete() {
		if (($id = URI::getSegment(1, true)) && is_numeric($id) && ($category = CMS::getCategoryById($id, false))) {
			if (CMS::deleteCategory($id, $error) !== false) {
				URI::redirect('/'.URI::relativePathPage().'/categories');
				return false;
			}

			$this->_default();
		} else {
			Errors::displayError(404, 'Not found');
		}
	}
}
?>