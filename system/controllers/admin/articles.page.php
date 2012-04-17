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
		0.1.2

	This script:
		Script de administração de artigos
	------------------------------------------------------------------------------------ --- -- - */

class Articles_Controller {
	function __construct() {
		if (!CMS::logged_in_user()) {
			URI::redirect('/'.URI::relative_path_page().'/login');
			return false;
		}

		Template::start();
	}

	function _default() {
		if (($pg = URI::get_segment(0, true)) && is_numeric($pg)) {
			$pg = (int)URI::get_segment(0, true);
			if ($pg < 1) $pg = 1;
		} else {
			$pg = 1;
		}

		if ($articles = CMS::get_articles_by_category(NULL, false, 'ASC', ($pg - 1) * 20, 20, $qtde)) {
			Template::assign('CMS_ArticleList', $articles);

			$pagination = new Pagination;
			$pagination->setSiteLink('/'.URI::relative_path_page().'/articles/[page]');
			$pagination->CalculateNumPages($qtde, 20);
			Template::assign('Pagination', $pagination->parse($pg));
		}
	}

	function edit() {
		if (($id = URI::get_segment(1, true)) && is_numeric($id) && ($article = CMS::get_article_by_id($id, false))) {
			if (!empty($_POST)) {
				if (empty($_POST['published'])) $_POST['published'] = 0;
				if (CMS::update_article($id, $_POST, $error) !== false) {
					URI::redirect('/'.URI::relative_path_page().'/articles');
					return false;
				}
				die('error: '.$error);
			}
			Template::assign('CMS_Article', $article);
			Template::assign('CMS_Categories', CMS::get_categories(true, 'title', 'ASC', 0, 0));
		} else {
			Errors::display_error(404, 'Not found');
		}
	}

	function add() {
		if (!empty($_POST)) {
			if (CMS::insert_article($_POST, $error) !== false) {
				URI::redirect('/'.URI::relative_path_page().'/articles');
				return false;
			}
			die('error: '.$error);
		} else {
			$article = array('article_id' => 0, 'published' => '1');
		}
		Template::assign('CMS_Article', $article);
		Template::assign('CMS_Categories', CMS::get_categories(true, 'title', 'ASC', 0, 0));
	}

	function delete() {
		if (($id = URI::get_segment(1, true)) && is_numeric($id) && ($article = CMS::get_article_by_id($id, false))) {
			if (CMS::delete_article($id, $error) !== false) {
				URI::redirect('/'.URI::relative_path_page().'/articles');
				return false;
			}

			$this->_default();
		} else {
			Errors::display_error(404, 'Not found');
		}
	}
}
?>